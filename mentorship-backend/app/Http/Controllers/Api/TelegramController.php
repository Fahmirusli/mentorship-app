<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Generate a linking token for user to connect Telegram
     */
    public function generateLinkToken(Request $request)
    {
        $user = $request->user();
        
        // Generate unique token
        $token = Str::random(32);
        
        // Store token with user ID for 10 minutes
        Cache::put("telegram_link_{$token}", $user->id, now()->addMinutes(10));
        
        $botUsername = config('telegram.bot_username', 'your_bot_username');
        
        return response()->json([
            'token' => $token,
            'link' => "https://t.me/{$botUsername}?start={$token}",
            'expires_in' => 600, // 10 minutes
            'instructions' => [
                '1. Click the link or open your Telegram bot',
                '2. Click START or send /start',
                '3. Your account will be automatically linked',
            ]
        ]);
    }

    /**
     * Verify and link Telegram account
     */
    public function linkAccount(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'chat_id' => 'required|string',
        ]);

        // Verify token
        $userId = Cache::get("telegram_link_{$validated['token']}");
        
        if (!$userId) {
            return response()->json([
                'message' => 'Invalid or expired token'
            ], 400);
        }

        // Update user's telegram_chat_id
        $user = \App\Models\User::find($userId);
        $user->telegram_chat_id = $validated['chat_id'];
        $user->save();

        // Clear the token
        Cache::forget("telegram_link_{$validated['token']}");

        // Send confirmation message
        try {
            $telegram = app(\App\Services\TelegramNotificationService::class);
            $telegram->sendToUser($user, "✅ <b>Account Linked Successfully!</b>\n\nYou will now receive notifications here.");
        } catch (\Exception $e) {
            \Log::warning('Failed to send confirmation: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Telegram account linked successfully',
            'user' => $user,
        ]);
    }

    /**
     * Unlink Telegram account
     */
    public function unlinkAccount(Request $request)
    {
        $user = $request->user();
        $user->telegram_chat_id = null;
        $user->save();

        return response()->json([
            'message' => 'Telegram account unlinked successfully'
        ]);
    }

    /**
     * Check if user has Telegram linked
     */
    public function checkStatus(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'linked' => !empty($user->telegram_chat_id),
            'chat_id' => $user->telegram_chat_id ? '***' . substr($user->telegram_chat_id, -4) : null,
        ]);
    }
}
