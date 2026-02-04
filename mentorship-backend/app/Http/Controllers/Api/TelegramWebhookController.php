<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Mentorship;
use App\Models\Job;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramWebhookController extends Controller
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('telegram.bot_token'));
    }

    public function webhook(Request $request)
    {
        $update = $this->telegram->getWebhookUpdate();
        
        // Handle callback queries (button clicks)
        if ($update->has('callback_query')) {
            return $this->handleCallbackQuery($update->getCallbackQuery());
        }
        
        // Handle regular messages/commands
        if ($update->has('message')) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = $message->getText();

            // Handle commands
            if (Str::startsWith($text, '/')) {
                return $this->handleCommand($chatId, $text, $message);
            }

            // Handle session summary input (if user is in feedback mode)
            if (Cache::has("awaiting_feedback_{$chatId}")) {
                return $this->handleSessionFeedback($chatId, $text);
            }
        }

        return response()->json(['ok' => true]);
    }

    protected function handleCommand($chatId, $text, $message)
    {
        $parts = explode(' ', $text);
        $command = $parts[0];
        $args = array_slice($parts, 1);

        switch ($command) {
            case '/start':
                return $this->handleStart($chatId, $args);
            
            case '/late':
                return $this->handleLate($chatId, $args);
            
            case '/myrequests':
                return $this->handleMyRequests($chatId);
            
            case '/mysessions':
                return $this->handleMySessions($chatId);
            
            case '/jobs':
                return $this->handleJobs($chatId, $args);
            
            case '/help':
                return $this->handleHelp($chatId);
            
            default:
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Unknown command. Type /help to see available commands."
                ]);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleStart($chatId, $args)
    {
        // Check if there's a linking token
        if (!empty($args[0]) && Str::startsWith($args[0], 'LINK-')) {
            $token = $args[0];
            $userId = Cache::get("telegram_link_{$token}");
            
            if ($userId) {
                $user = User::find($userId);
                $user->telegram_chat_id = $chatId;
                $user->save();
                
                Cache::forget("telegram_link_{$token}");
                
                // Send welcome with menu
                $keyboard = $this->getMainMenuKeyboard($user);
                
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "✅ <b>Account Linked Successfully!</b>\n\n" .
                              "Welcome {$user->name}! You will now receive notifications here.\n\n" .
                              "Choose an option below:",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
                
                return response()->json(['ok' => true]);
            }
        }

        // Regular welcome message with menu button
        $keyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => '🔗 Link Account', 'url' => 'https://uplifts.dev/dashboard'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '❓ Help', 'callback_data' => 'help'])
            ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "👋 <b>Welcome to Uplifts Mentorship Bot!</b>\n\n" .
                      "To link your account:\n" .
                      "1. Click 'Link Account' below\n" .
                      "2. Go to your dashboard\n" .
                      "3. Click 'Connect Telegram'\n" .
                      "4. Use the provided link",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);

        return response()->json(['ok' => true]);
    }

    protected function getMainMenuKeyboard($user)
    {
        $keyboard = Keyboard::make()->inline();

        if ($user->role === 'mentor') {
            $keyboard->row([
                Keyboard::inlineButton(['text' => '📩 My Requests', 'callback_data' => 'my_requests']),
                Keyboard::inlineButton(['text' => '📅 My Sessions', 'callback_data' => 'my_sessions'])
            ]);
            $keyboard->row([
                Keyboard::inlineButton(['text' => '⏰ I\'m Running Late', 'callback_data' => 'running_late'])
            ]);
        } else {
            $keyboard->row([
                Keyboard::inlineButton(['text' => '📅 My Sessions', 'callback_data' => 'my_sessions']),
                Keyboard::inlineButton(['text' => '👨‍🏫 Find Mentors', 'url' => 'https://uplifts.dev/mentee/find-mentor'])
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => '💼 Job Opportunities', 'callback_data' => 'jobs']),
            Keyboard::inlineButton(['text' => '❓ Help', 'callback_data' => 'help'])
        ]);

        return $keyboard;
    }

    protected function handleLate($chatId, $args)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        if (!$user) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Please link your account first. Type /start"
            ]);
            return response()->json(['ok' => true]);
        }

        $minutes = isset($args[0]) ? intval($args[0]) : 5;
        
        // Find today's upcoming appointment
        $appointment = Appointment::whereHas('mentorship', function($q) use ($user) {
                $q->where('mentor_id', $user->id);
            })
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<', now()->addHours(2))
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->first();

        if (!$appointment) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "No upcoming sessions found in the next 2 hours."
            ]);
            return response()->json(['ok' => true]);
        }

        // Notify mentee
        $mentee = $appointment->mentorship->mentee;
        $telegram = app(TelegramNotificationService::class);
        $telegram->sendToUser($mentee, 
            "⏰ <b>Session Update</b>\n\n" .
            "Your mentor {$user->name} is running {$minutes} minutes late.\n" .
            "Revised start time: " . now()->addMinutes($minutes)->format('H:i')
        );

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ Mentee has been notified about the {$minutes} minute delay."
        ]);

        return response()->json(['ok' => true]);
    }

    protected function handleMyRequests($chatId)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        if (!$user || !$user->isMentor()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ This command is for mentors only."
            ]);
            return response()->json(['ok' => true]);
        }

        $pendingRequests = Mentorship::where('mentor_id', $user->id)
            ->where('status', 'pending')
            ->with('mentee')
            ->get();

        if ($pendingRequests->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ No pending requests at the moment."
            ]);
            return response()->json(['ok' => true]);
        }

        foreach ($pendingRequests as $request) {
            $keyboard = Keyboard::make()
                ->inline()
                ->row([
                    Keyboard::inlineButton(['text' => '✅ Accept', 'callback_data' => "accept_{$request->id}"]),
                    Keyboard::inlineButton(['text' => '❌ Reject', 'callback_data' => "reject_{$request->id}"])
                ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📩 <b>New Request</b>\n\n" .
                          "From: {$request->mentee->name}\n" .
                          "Email: {$request->mentee->email}\n" .
                          "Goal: {$request->goal}",
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleMySessions($chatId)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        if (!$user) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Please link your account first. Type /start"
            ]);
            return response()->json(['ok' => true]);
        }

        $appointments = Appointment::whereHas('mentorship', function($q) use ($user) {
                $q->where('mentor_id', $user->id)->orWhere('mentee_id', $user->id);
            })
            ->where('scheduled_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        if ($appointments->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📅 No upcoming sessions scheduled."
            ]);
            return response()->json(['ok' => true]);
        }

        $message = "📅 <b>Your Upcoming Sessions:</b>\n\n";
        foreach ($appointments as $apt) {
            $isMentor = $apt->mentorship->mentor_id == $user->id;
            $other = $isMentor ? $apt->mentorship->mentee : $apt->mentorship->mentor;
            $role = $isMentor ? 'Mentee' : 'Mentor';
            
            $message .= "• {$apt->scheduled_at->format('M d, H:i')}\n";
            $message .= "  {$role}: {$other->name}\n";
            $message .= "  Duration: {$apt->duration_minutes} min\n\n";
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        return response()->json(['ok' => true]);
    }

    protected function handleJobs($chatId, $args)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        if (!$user) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Please link your account first. Type /start"
            ]);
            return response()->json(['ok' => true]);
        }

        // Get user's skills or search term
        $searchTerm = !empty($args) ? implode(' ', $args) : null;
        
        $query = Job::where('is_active', true);
        
        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('company', 'like', "%{$searchTerm}%")
                  ->orWhere('location', 'like', "%{$searchTerm}%");
            });
        } elseif ($user->skills) {
            // Match jobs based on user skills
            foreach ($user->skills as $skill) {
                $query->orWhere('title', 'like', "%{$skill}%");
            }
        }

        $jobs = $query->orderBy('created_at', 'desc')->take(5)->get();

        if ($jobs->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🔍 No jobs found. Try: /jobs Laravel"
            ]);
            return response()->json(['ok' => true]);
        }

        $message = "💼 <b>Available Jobs:</b>\n\n";
        foreach ($jobs as $job) {
            $message .= "🏢 <b>{$job->title}</b>\n";
            $message .= "   Company: {$job->company}\n";
            $message .= "   Location: {$job->location}\n";
            if ($job->salary) {
                $message .= "   Salary: {$job->salary}\n";
            }
            $message .= "   <a href='{$job->url}'>Apply Here</a>\n\n";
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ]);

        return response()->json(['ok' => true]);
    }

    protected function handleHelp($chatId)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        $message = "🤖 <b>Bot Commands & Features:</b>\n\n";
        
        if ($user && $user->role === 'mentor') {
            $message .= "👨‍🏫 <b>Mentor Features:</b>\n";
            $message .= "• View and manage mentorship requests\n";
            $message .= "• Notify mentees if running late\n";
            $message .= "• Track upcoming sessions\n\n";
        } else if ($user) {
            $message .= "👨‍🎓 <b>Mentee Features:</b>\n";
            $message .= "• View your scheduled sessions\n";
            $message .= "• Browse job opportunities\n";
            $message .= "• Receive session reminders\n\n";
        }
        
        $message .= "📱 <b>Commands:</b>\n";
        $message .= "/start - Show main menu\n";
        $message .= "/help - Show this help message\n";
        $message .= "/mysessions - View upcoming sessions\n";
        $message .= "/jobs [keyword] - Search for jobs\n";
        
        if ($user && $user->role === 'mentor') {
            $message .= "/myrequests - View pending requests\n";
            $message .= "/late [minutes] - Notify mentee of delay\n";
        }

        $keyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => '📱 Main Menu', 'callback_data' => 'main_menu'])
            ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);

        return response()->json(['ok' => true]);
    }

    protected function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $data = $callbackQuery->getData();
        $queryId = $callbackQuery->getId();
        
        // Handle menu callbacks
        if ($data == 'my_requests') {
            $this->handleMyRequests($chatId);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $queryId]);
        } elseif ($data == 'my_sessions') {
            $this->handleMySessions($chatId);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $queryId]);
        } elseif ($data == 'running_late') {
            $this->promptLateNotification($chatId, $queryId);
        } elseif ($data == 'jobs') {
            $this->handleJobs($chatId, []);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $queryId]);
        } elseif ($data == 'help') {
            $this->handleHelp($chatId);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $queryId]);
        } elseif (Str::startsWith($data, 'accept_')) {
            $mentorshipId = str_replace('accept_', '', $data);
            $this->acceptMentorship($chatId, $mentorshipId, $queryId);
        } elseif (Str::startsWith($data, 'reject_')) {
            $mentorshipId = str_replace('reject_', '', $data);
            $this->rejectMentorship($chatId, $mentorshipId, $queryId);
        } elseif (Str::startsWith($data, 'late_')) {
            $minutes = str_replace('late_', '', $data);
            $this->sendLateNotification($chatId, $queryId, intval($minutes));
        } elseif ($data == 'provide_feedback') {
            $this->initiateSessionFeedback($chatId, $queryId);
        } elseif ($data == 'main_menu') {
            $user = User::where('telegram_chat_id', $chatId)->first();
            if ($user) {
                $keyboard = $this->getMainMenuKeyboard($user);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "📱 <b>Main Menu</b>\n\nChoose an option:",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
            }
            $this->telegram->answerCallbackQuery(['callback_query_id' => $queryId]);
        }

        return response()->json(['ok' => true]);
    }

    protected function promptLateNotification($chatId, $queryId)
    {
        $keyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => '5 minutes', 'callback_data' => 'late_5']),
                Keyboard::inlineButton(['text' => '10 minutes', 'callback_data' => 'late_10'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '15 minutes', 'callback_data' => 'late_15']),
                Keyboard::inlineButton(['text' => '30 minutes', 'callback_data' => 'late_30'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '« Back', 'callback_data' => 'main_menu'])
            ]);

        $this->telegram->answerCallbackQuery(['callback_query_id' => $queryId]);
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "⏰ <b>How late will you be?</b>\n\nSelect the delay time:",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }

    protected function sendLateNotification($chatId, $queryId, $minutes)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        if (!$user) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $queryId,
                'text' => '❌ Account not linked'
            ]);
            return;
        }

        // Find today's upcoming appointment
        $appointment = Appointment::whereHas('mentorship', function($q) use ($user) {
                $q->where('mentor_id', $user->id);
            })
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<', now()->addHours(2))
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->first();

        if (!$appointment) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $queryId,
                'text' => 'No upcoming sessions found in the next 2 hours.'
            ]);
            return;
        }

        // Notify mentee
        $mentee = $appointment->mentorship->mentee;
        $telegram = app(TelegramNotificationService::class);
        $telegram->sendToUser($mentee, 
            "⏰ <b>Session Update</b>\n\n" .
            "Your mentor {$user->name} is running {$minutes} minutes late.\n" .
            "Revised start time: " . now()->addMinutes($minutes)->format('H:i')
        );

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $queryId,
            'text' => "✅ Mentee notified about {$minutes} min delay"
        ]);
    }

    protected function acceptMentorship($chatId, $mentorshipId, $queryId)
    {
        $mentorship = Mentorship::find($mentorshipId);
        
        if (!$mentorship) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $queryId,
                'text' => 'Request not found'
            ]);
            return;
        }

        $mentorship->status = 'active';
        $mentorship->save();

        // Notify mentee
        $telegram = app(TelegramNotificationService::class);
        $telegram->sendToUser($mentorship->mentee, 
            "🎉 <b>Good News!</b>\n\n" .
            "Mentor {$mentorship->mentor->name} accepted your request!\n\n" .
            "You can now schedule sessions together."
        );

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $queryId,
            'text' => '✅ Request accepted!'
        ]);

        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $mentorship->id,
            'text' => "✅ <b>Accepted</b>\n\nRequest from {$mentorship->mentee->name}",
            'parse_mode' => 'HTML'
        ]);
    }

    protected function rejectMentorship($chatId, $mentorshipId, $queryId)
    {
        $mentorship = Mentorship::find($mentorshipId);
        
        if (!$mentorship) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $queryId,
                'text' => 'Request not found'
            ]);
            return;
        }

        $mentorship->status = 'rejected';
        $mentorship->save();

        // Notify mentee
        $telegram = app(TelegramNotificationService::class);
        $telegram->sendToUser($mentorship->mentee, 
            "😔 <b>Request Update</b>\n\n" .
            "Mentor {$mentorship->mentor->name} is currently unavailable.\n\n" .
            "Please try another mentor from the platform."
        );

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $queryId,
            'text' => '❌ Request rejected'
        ]);
    }

    protected function initiateSessionFeedback($chatId, $queryId)
    {
        Cache::put("awaiting_feedback_{$chatId}", true, now()->addMinutes(10));
        
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $queryId,
            'text' => 'Please type your session summary'
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "📝 Please enter your session summary:\n\nDescribe what you covered, any challenges, and next steps."
        ]);
    }

    protected function handleSessionFeedback($chatId, $text)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        if (!$user) {
            return response()->json(['ok' => true]);
        }

        // Find most recent completed session
        $appointment = Appointment::whereHas('mentorship', function($q) use ($user) {
                $q->where('mentor_id', $user->id);
            })
            ->where('status', 'completed')
            ->whereNull('notes')
            ->orderBy('scheduled_at', 'desc')
            ->first();

        if ($appointment) {
            $appointment->notes = $text;
            $appointment->save();
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ Session summary saved!"
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ No recent session found."
            ]);
        }

        Cache::forget("awaiting_feedback_{$chatId}");
        return response()->json(['ok' => true]);
    }
}
