<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ScrapeJobs::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run job scraping every night at 2 AM
        $schedule->command('jobs:scrape')
                 ->dailyAt('02:00')
                 ->timezone('Asia/Kuala_Lumpur')
                 ->emailOutputOnFailure('admin@example.com'); // Change to your email
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}