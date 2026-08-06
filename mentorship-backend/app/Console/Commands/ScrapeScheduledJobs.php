<?php

namespace App\Console\Commands;

use App\Models\JobScrapeSchedule;
use App\Services\JobScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ScrapeScheduledJobs extends Command
{
    protected $signature = 'jobs:scrape-scheduled';
    protected $description = 'Run job scraping when the admin-configured time is reached';

    public function handle(JobScraperService $scraper)
    {
        $schedule = JobScrapeSchedule::first();

        if (!$schedule || !$schedule->enabled) {
            return 0;
        }

        $timezone = $schedule->timezone ?: 'Asia/Kuala_Lumpur';
        $now = Carbon::now($timezone);

        $expectedRunTime = Carbon::parse($schedule->run_time)->format('H:i');

        if ($now->format('H:i') !== $expectedRunTime) {
            return 0;
        }

        if ($schedule->last_run_at && $schedule->last_run_at->timezone($timezone)->isSameDay($now)) {
            return 0;
        }

        $keyword = $schedule->keyword ?: 'Software Engineer';

        try {
            Log::info("Scheduled scrape triggered for keyword: {$keyword}");
            $results = $scraper->scrapeAll($keyword);

            $schedule->last_run_at = $now->toDateTimeString();
            $schedule->last_run_status = "ok ({$results['total']})";
            $schedule->save();
        } catch (\Throwable $e) {
            $schedule->last_run_at = $now->toDateTimeString();
            $schedule->last_run_status = 'failed';
            $schedule->save();

            Log::error('Scheduled scrape failed: ' . $e->getMessage());
        }

        return 0;
    }
}
