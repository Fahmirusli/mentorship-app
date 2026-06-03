<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ScrapeJobs::class,
        \App\Console\Commands\ScrapeScheduledJobs::class,
    ];


    protected function schedule(Schedule $schedule)
    {
        $schedule->command('jobs:scrape-scheduled')
            ->everyMinute()
            ->emailOutputOnFailure('admin@example.com');
        

    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}