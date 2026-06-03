<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobScraperService;

class ScrapeJobs extends Command
{
    protected $signature = 'jobs:scrape {--keyword=Software Engineer}';
    protected $description = 'Fetch jobs from RapidAPI JSearch';

    public function handle(JobScraperService $scraper)
    {
        $this->info('Starting job scraping...');

        $keyword = $this->option('keyword');
        $results = $scraper->scrapeAll($keyword);

        $this->info("Scraped {$results['total']} jobs");
        
        return 0;
    }
}