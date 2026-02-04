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
     * Notify about new appointment booking
     */
    public function notifyNewAppointment($appointment)
    {
        $mentor = $appointment->mentorship->mentor->name;
        $mentee = $appointment->mentorship->mentee->name;
        $date = $appointment->scheduled_at->format('M d, Y H:i');
        $duration = $appointment->duration_minutes;

        $message = "🗓 <b>New Appointment Booked</b>\n\n";
        $message .= "👨‍🏫 <b>Mentor:</b> {$mentor}\n";
        $message .= "👤 <b>Mentee:</b> {$mentee}\n";
        $message .= "📅 <b>Date:</b> {$date}\n";
        $message .= "⏱ <b>Duration:</b> {$duration} minutes\n";
        $message .= "💰 <b>Fee:</b> RM {$appointment->fee}";

        return $this->sendMessage($message);
    }

    /**
     * Notify about appointment rescheduled
     */
    public function notifyAppointmentRescheduled($appointment, $oldDate)
    {
        $mentor = $appointment->mentorship->mentor->name;
        $mentee = $appointment->mentorship->mentee->name;
        $newDate = $appointment->scheduled_at->format('M d, Y H:i');

        $message = "🔄 <b>Appointment Rescheduled</b>\n\n";
        $message .= "👨‍🏫 <b>Mentor:</b> {$mentor}\n";
        $message .= "👤 <b>Mentee:</b> {$mentee}\n";
        $message .= "📅 <b>New Date:</b> {$newDate}\n";
        $message .= "🕐 <b>Old Date:</b> {$oldDate}";

        return $this->sendMessage($message);
    }

    /**
     * Notify about appointment cancellation
     */
    public function notifyAppointmentCancelled($appointment)
    {
        $mentor = $appointment->mentorship->mentor->name;
        $mentee = $appointment->mentorship->mentee->name;
        $date = $appointment->scheduled_at->format('M d, Y H:i');

        $message = "❌ <b>Appointment Cancelled</b>\n\n";
        $message .= "👨‍🏫 <b>Mentor:</b> {$mentor}\n";
        $message .= "👤 <b>Mentee:</b> {$mentee}\n";
        $message .= "📅 <b>Date:</b> {$date}";

        return $this->sendMessage($message);
    }

    /**
     * Notify about new feedback received
     */
    public function notifyNewFeedback($feedback)
    {
        $mentor = $feedback->appointment->mentorship->mentor->name;
        $mentee = $feedback->appointment->mentorship->mentee->name;
        $rating = $feedback->rating;

        $message = "⭐ <b>New Feedback Received</b>\n\n";
        $message .= "👨‍🏫 <b>Mentor:</b> {$mentor}\n";
        $message .= "👤 <b>Mentee:</b> {$mentee}\n";
        $message .= "⭐ <b>Rating:</b> {$rating}/5\n";
        $message .= "💬 <b>Comment:</b> " . substr($feedback->comment, 0, 100);

        return $this->sendMessage($message);
    }

    /**
     * Notify about new user registration
     */
    public function notifyNewUser($user)
    {
        $role = ucfirst($user->role);
        
        $message = "👤 <b>New User Registered</b>\n\n";
        $message .= "📛 <b>Name:</b> {$user->name}\n";
        $message .= "📧 <b>Email:</b> {$user->email}\n";
        $message .= "🎭 <b>Role:</b> {$role}\n";
        $message .= "📅 <b>Registered:</b> " . $user->created_at->format('M d, Y H:i');

        return $this->sendMessage($message);
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
