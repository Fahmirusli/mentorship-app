<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ScrapeJobs::class,
        \App\Console\Commands\SendSessionReminders::class,
        \App\Console\Commands\SendDailyTips::class,
        \App\Console\Commands\SendJobAlerts::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run job scraping every night at 2 AM
        $schedule->command('jobs:scrape')
                 ->dailyAt('02:00')
                 ->timezone('Asia/Kuala_Lumpur')
                 ->emailOutputOnFailure('admin@example.com'); // Change to your email
        
        // Check for session reminders every 2 minutes
        $schedule->command('telegram:session-reminders')
                 ->everyTwoMinutes();
        
        // Send daily motivational tips at 9 AM
        $schedule->command('telegram:daily-tips')
                 ->dailyAt('09:00')
                 ->timezone('Asia/Kuala_Lumpur');
        
        // Send job alerts right after scraping completes at 2:15 AM
        $schedule->command('telegram:job-alerts')
                 ->dailyAt('02:15')
                 ->timezone('Asia/Kuala_Lumpur');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}