<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\NotificationLog;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function getConversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne:id,name,email,role,profile_image,bio', 'userTwo:id,name,email,role,profile_image,bio', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conv) use ($userId) {
                $otherUser = $conv->user_one_id == $userId ? $conv->userTwo : $conv->userOne;
                $unreadCount = $conv->unreadCountFor($userId);

                return [
                    'id' => $conv->id,
                    'other_user' => $otherUser,
                    'last_message' => $conv->latestMessage ? [
                        'body' => $conv->latestMessage->body,
                        'sender_id' => $conv->latestMessage->sender_id,
                        'created_at' => $conv->latestMessage->created_at->toISOString(),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'last_message_at' => $conv->last_message_at?->toISOString(),
                ];
            });

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Get or create a conversation with a specific user, and return messages.
     */
    public function getMessages(Request $request, $otherUserId)
    {
        $userId = $request->user()->id;

        // Prevent chatting with yourself
        if ($userId == $otherUserId) {
            return response()->json(['message' => 'Cannot chat with yourself'], 400);
        }

        $conversation = Conversation::findOrCreateBetween($userId, $otherUserId);

        // Mark all messages from the other user as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $otherUserId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // Fetch messages (paginated, newest last)
        $messages = Message::where('conversation_id', $conversation->id)
            ->with('sender:id,name,profile_image')
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender->name,
                    'body' => $msg->body,
                    'is_read' => $msg->is_read,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            });

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message to a specific user.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        $senderId = $request->user()->id;
        $receiverId = $request->receiver_id;

        if ($senderId == $receiverId) {
            return response()->json(['message' => 'Cannot send message to yourself'], 400);
        }

        $conversation = Conversation::findOrCreateBetween($senderId, $receiverId);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'body' => $request->body,
        ]);

        // Update conversation's last_message_at
        $conversation->update(['last_message_at' => now()]);

        // Create a notification for the receiver
        NotificationLog::notify(
            $receiverId,
            'message',
            'New message from ' . $request->user()->name,
            $request->body,
            ['sender_id' => $senderId, 'conversation_id' => $conversation->id]
        );

        try {
            // Broadcast the message event
            broadcast(new \App\Events\MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to broadcast message: ' . $e->getMessage());
            // Fail silently so the sender still gets a 201 success response
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $request->user()->name,
                'body' => $message->body,
                'is_read' => false,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Poll for new messages in a conversation (simple polling for real-time feel).
     */
    public function pollMessages(Request $request, $conversationId)
    {
        $request->validate([
            'after_id' => 'sometimes|integer',
        ]);

        $userId = $request->user()->id;
        $afterId = $request->get('after_id', 0);

        // Verify user is part of the conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            })->first();

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        // Get new messages after the given ID
        $messages = Message::where('conversation_id', $conversationId)
            ->where('id', '>', $afterId)
            ->with('sender:id,name,profile_image')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender->name,
                    'body' => $msg->body,
                    'is_read' => $msg->is_read,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            });

        // Mark incoming messages as read
        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['messages' => $messages]);
    }
}
