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

        // The isSameDay check was removed here to allow testing multiple times in one day.
        // Since H:i only matches once per 24 hours, it will still only run once automatically!
        $keyword = $schedule->keyword ?: 'Software Engineer';

        try {
            Log::info("Scheduled scrape triggered for keyword: {$keyword}");
            $results = $scraper->scrapeAll($keyword);

            $schedule->last_run_at = $now->toDateTimeString();
            $schedule->last_run_status = "ok ({$results['total']})";
            $schedule->save();
        } catch (\Throwable $e) {
            $schedule->last_run_at = $now->toDateTimeString();
            // Truncate the error message so it fits in the database column
            $errorMsg = substr($e->getMessage(), 0, 150);
            $schedule->last_run_status = 'failed: ' . $errorMsg;
            $schedule->save();

            Log::error('Scheduled scrape failed: ' . $e->getMessage());
        }

        return 0;
    }
}
