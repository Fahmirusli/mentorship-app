<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Job;
use App\Services\TelegramNotificationService;

class SendJobAlerts extends Command
{
    protected $signature = 'telegram:job-alerts';
    protected $description = 'Send job alerts to mentees based on their skills';

    public function handle()
    {
        $telegram = app(TelegramNotificationService::class);
        
        // Get mentees with Telegram and skills
        $mentees = User::whereHas('menteeMentorships')
            ->whereNotNull('telegram_chat_id')
            ->whereNotNull('skills')
            ->get();

        if ($mentees->isEmpty()) {
            $this->info('No mentees with skills found');
            return Command::SUCCESS;
        }

        // Get today's newly added jobs
        $newJobs = Job::where('is_active', true)
            ->whereDate('created_at', today())
            ->get();

        if ($newJobs->isEmpty()) {
            $this->info('No new jobs today');
            return Command::SUCCESS;
        }

        $alertsSent = 0;

        foreach ($mentees as $mentee) {
            $matchedJobs = collect();

            // Find jobs matching mentee's skills
            foreach ($newJobs as $job) {
                foreach ($mentee->skills as $skill) {
                    if (stripos($job->title, $skill) !== false || 
                        stripos($job->description, $skill) !== false) {
                        $matchedJobs->push($job);
                        break;
                    }
                }
            }

            // Send alert if there are matches
            if ($matchedJobs->isNotEmpty()) {
                $message = "💼 <b>New Jobs Matching Your Skills!</b>\n\n";
                
                foreach ($matchedJobs->take(3) as $job) {
                    $message .= "🏢 <b>{$job->title}</b>\n";
                    $message .= "   {$job->company} - {$job->location}\n";
                    if ($job->salary) {
                        $message .= "   💰 {$job->salary}\n";
                    }
                    $message .= "   <a href='{$job->url}'>Apply Now</a>\n\n";
                }

                $message .= "Type /jobs to see more opportunities!";

                $telegram->sendToUser($mentee, $message);
                $alertsSent++;
            }
        }

        $this->info("Job alerts sent to {$alertsSent} mentees");
        return Command::SUCCESS;
    }
}
