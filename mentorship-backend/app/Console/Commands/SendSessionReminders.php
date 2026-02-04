<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;

class SendSessionReminders extends Command
{
    protected $signature = 'telegram:session-reminders';
    protected $description = 'Send Telegram reminders for upcoming sessions';

    public function handle()
    {
        $telegram = app(TelegramNotificationService::class);
        
        // Get appointments in 30 minutes
        $thirtyMinutes = Appointment::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [
                Carbon::now()->addMinutes(28),
                Carbon::now()->addMinutes(32)
            ])
            ->with(['mentorship.mentor', 'mentorship.mentee'])
            ->get();

        foreach ($thirtyMinutes as $appointment) {
            $mentor = $appointment->mentorship->mentor;
            $mentee = $appointment->mentorship->mentee;
            $time = $appointment->scheduled_at->format('H:i');

            // Remind mentor
            $telegram->sendToUser($mentor, 
                "⏰ <b>Session Reminder</b>\n\n" .
                "Your session with {$mentee->name} starts in 30 minutes at {$time}.\n\n" .
                "Running late? Use /late [minutes]"
            );

            // Remind mentee
            $telegram->sendToUser($mentee,
                "⏰ <b>Session Reminder</b>\n\n" .
                "Your session with {$mentor->name} starts in 30 minutes at {$time}.\n\n" .
                "Please be ready!"
            );
        }

        // Get appointments in 5 minutes
        $fiveMinutes = Appointment::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [
                Carbon::now()->addMinutes(4),
                Carbon::now()->addMinutes(6)
            ])
            ->with(['mentorship.mentor', 'mentorship.mentee'])
            ->get();

        foreach ($fiveMinutes as $appointment) {
            $mentor = $appointment->mentorship->mentor;
            $mentee = $appointment->mentorship->mentee;
            $time = $appointment->scheduled_at->format('H:i');

            // Final reminder to both
            $telegram->sendToUser($mentor,
                "🔔 <b>Starting Soon!</b>\n\n" .
                "Your session with {$mentee->name} starts in 5 minutes at {$time}!"
            );

            $telegram->sendToUser($mentee,
                "🔔 <b>Starting Soon!</b>\n\n" .
                "Your session with {$mentor->name} starts in 5 minutes at {$time}!"
            );
        }

        $this->info('Session reminders sent: ' . 
                   ($thirtyMinutes->count() + $fiveMinutes->count()) . ' appointments');
        
        return Command::SUCCESS;
    }
}
