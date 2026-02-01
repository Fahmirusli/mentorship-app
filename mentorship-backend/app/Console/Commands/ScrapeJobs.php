<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobScraperService;

class ScrapeJobs extends Command
{
    protected $signature = 'jobs:scrape';
    protected $description = 'Scrape jobs from JobStreet, LinkedIn, and Hiredly';

    public function handle(JobScraperService $scraper)
    {
        $this->info('Starting job scraping...');
        
        $results = $scraper->scrapeAll();
        
        $this->info("Scraped {$results['total']} jobs");
        $this->info("JobStreet: " . ($results['jobstreet'] ?? 0));
        $this->info("LinkedIn: " . ($results['linkedin'] ?? 0));
        $this->info("Hiredly: " . ($results['hiredly'] ?? 0));
        
        return 0;
    }
}