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

        $keywordsOption = $this->option('keyword');
        $keywords = explode(',', $keywordsOption);
        
        $totalScraped = 0;
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) continue;
            
            $this->info("Scraping for keyword: {$keyword}");
            $results = $scraper->scrapeAll($keyword);
            $scrapedForKeyword = $results['total'] ?? 0;
            $totalScraped += $scrapedForKeyword;
            $this->info("Scraped {$scrapedForKeyword} jobs for {$keyword}");
        }

        $this->info("Total scraped: {$totalScraped} jobs");
        
        return 0;
    }
}