<?php

namespace App\Services;

use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    protected $telegram;
    protected $chatId;
    protected $enabled;

    public function __construct()
    {
        $botToken = config('telegram.bot_token');
        $this->chatId = config('telegram.chat_id');
        $this->enabled = !empty($botToken) && !empty($this->chatId);

        if ($this->enabled) {
            try {
                $this->telegram = new Api($botToken);
            } catch (\Exception $e) {
                Log::error('Telegram Bot initialization failed: ' . $e->getMessage());
                $this->enabled = false;
            }
        }
    }

    /**
     * Send a notification message
     */
    public function sendMessage($message, $chatId = null)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId ?? $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message to a specific user
     */
    public function sendToUser($user, $message)
    {
        if (!$this->enabled) {
            return false;
        }

        // If user has Telegram linked, send to them
        if ($user->telegram_chat_id) {
            return $this->sendMessage($message, $user->telegram_chat_id);
        }

        // Otherwise send to admin (fallback)
        return $this->sendMessage("👤 {$user->name} - {$message}", $this->chatId);
    }

    /**
     * Notify about new appointment booking
     */
    public function notifyNewAppointment($appointment)
    {
        $mentor = $appointment->mentorship->mentor;
        $mentee = $appointment->mentorship->mentee;
        $date = $appointment->scheduled_at->format('M d, Y H:i');
        $duration = $appointment->duration_minutes;

        // Notify mentor
        $mentorMessage = "🗓 <b>New Appointment Booked</b>\n\n";
        $mentorMessage .= "👤 <b>Mentee:</b> {$mentee->name}\n";
        $mentorMessage .= "📅 <b>Date:</b> {$date}\n";
        $mentorMessage .= "⏱ <b>Duration:</b> {$duration} minutes\n";
        $mentorMessage .= "💰 <b>Fee:</b> RM {$appointment->fee}";
        
        $this->sendToUser($mentor, $mentorMessage);

        // Notify mentee
        $menteeMessage = "✅ <b>Appointment Confirmed</b>\n\n";
        $menteeMessage .= "👨‍🏫 <b>Mentor:</b> {$mentor->name}\n";
        $menteeMessage .= "📅 <b>Date:</b> {$date}\n";
        $menteeMessage .= "⏱ <b>Duration:</b> {$duration} minutes\n";
        $menteeMessage .= "💰 <b>Fee:</b> RM {$appointment->fee}";
        
        $this->sendToUser($mentee, $menteeMessage);

        return true;
    }

    /**
     * Notify about appointment rescheduled
     */
    public function notifyAppointmentRescheduled($appointment, $oldDate)
    {
        $mentor = $appointment->mentorship->mentor;
        $mentee = $appointment->mentorship->mentee;
        $newDate = $appointment->scheduled_at->format('M d, Y H:i');

        $message = "🔄 <b>Appointment Rescheduled</b>\n\n";
        $message .= "📅 <b>New Date:</b> {$newDate}\n";
        $message .= "🕐 <b>Old Date:</b> {$oldDate}";

        // Notify both parties
        $mentorMessage = $message . "\n👤 <b>Mentee:</b> {$mentee->name}";
        $menteeMessage = $message . "\n👨‍🏫 <b>Mentor:</b> {$mentor->name}";
        
        $this->sendToUser($mentor, $mentorMessage);
        $this->sendToUser($mentee, $menteeMessage);

        return true;
    }

    /**
     * Notify about appointment cancellation
     */
    public function notifyAppointmentCancelled($appointment)
    {
        $mentor = $appointment->mentorship->mentor;
        $mentee = $appointment->mentorship->mentee;
        $date = $appointment->scheduled_at->format('M d, Y H:i');

        $message = "❌ <b>Appointment Cancelled</b>\n\n";
        $message .= "📅 <b>Date:</b> {$date}";

        // Notify both parties
        $mentorMessage = $message . "\n👤 <b>Mentee:</b> {$mentee->name}";
        $menteeMessage = $message . "\n👨‍🏫 <b>Mentor:</b> {$mentor->name}";
        
        $this->sendToUser($mentor, $mentorMessage);
        $this->sendToUser($mentee, $menteeMessage);

        return true;
    }

    /**
     * Notify about new feedback received
     */
    public function notifyNewFeedback($feedback)
    {
        $mentor = $feedback->appointment->mentorship->mentor;
        $mentee = $feedback->appointment->mentorship->mentee;
        $rating = $feedback->rating;

        $message = "⭐ <b>New Feedback Received</b>\n\n";
        $message .= "👤 <b>From:</b> {$mentee->name}\n";
        $message .= "⭐ <b>Rating:</b> {$rating}/5\n";
        $message .= "💬 <b>Comment:</b> " . substr($feedback->comment, 0, 100);

        // Notify mentor about feedback
        $this->sendToUser($mentor, $message);

        return true;
    }

    /**
     * Notify about new user registration
     */
    public function notifyNewUser($user)
    {
        $role = ucfirst($user->role);
        
        $welcomeMessage = "👋 <b>Welcome to Uplifts Mentorship!</b>\n\n";
        $welcomeMessage .= "Hi {$user->name}! Your account has been created successfully.\n\n";
        $welcomeMessage .= "🎭 <b>Role:</b> {$role}\n";
        $welcomeMessage .= "📧 <b>Email:</b> {$user->email}\n\n";
        $welcomeMessage .= "💡 <b>Tip:</b> To receive notifications here, link your Telegram account in your profile settings.";
        
        // Try to send welcome message to user if they have Telegram linked
        if ($user->telegram_chat_id) {
            $this->sendToUser($user, $welcomeMessage);
        }

        // Also notify admin about new registration
        $adminMessage = "👤 <b>New User Registered</b>\n\n";
        $adminMessage .= "📛 <b>Name:</b> {$user->name}\n";
        $adminMessage .= "📧 <b>Email:</b> {$user->email}\n";
        $adminMessage .= "🎭 <b>Role:</b> {$role}\n";
        $adminMessage .= "📅 <b>Registered:</b> " . $user->created_at->format('M d, Y H:i');

        return $this->sendMessage($adminMessage);
    }

    /**
     * Notify about payment completion
     */
    public function notifyPaymentCompleted($transaction)
    {
        $message = "💳 <b>Payment Completed</b>\n\n";
        $message .= "💰 <b>Amount:</b> RM {$transaction->amount}\n";
        $message .= "🔖 <b>Bill Code:</b> {$transaction->bill_code}\n";
        $message .= "✅ <b>Status:</b> {$transaction->status}";

        return $this->sendMessage($message);
    }

    /**
     * Send custom notification to specific chat
     */
    public function notify($title, $details, $chatId = null)
    {
        $message = "<b>{$title}</b>\n\n{$details}";
        return $this->sendMessage($message, $chatId);
    }

    /**
     * Check if Telegram notifications are enabled
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
