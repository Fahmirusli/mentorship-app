<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobScraperService;
use App\Models\Job;

class TestJobScraping extends Command
{
    protected $signature = 'jobs:test-scrape';
    protected $description = 'Test job scraping functionality and display results';

    public function handle(JobScraperService $scraper)
    {
        $this->info('🚀 Starting job scraping test...');
        $this->newLine();
        
        // Show jobs before scraping
        $beforeCount = Job::count();
        $this->info("📊 Jobs in database before scraping: {$beforeCount}");
        $this->newLine();
        
        // Run scraper
        $this->info('🔍 Scraping jobs from external sources...');
        $results = $scraper->scrapeAll();
        
        $this->newLine();
        $this->info('✅ Scraping completed!');
        $this->newLine();
        
        // Display results
        $this->table(
            ['Source', 'Jobs Found'],
            [
                ['JobStreet', $results['jobstreet']],
                ['LinkedIn', $results['linkedin']],
                ['Hiredly', $results['hiredly']],
                ['─────────', '──────────'],
                ['Total', $results['total']]
            ]
        );
        
        // Show jobs after scraping
        $afterCount = Job::count();
        $this->newLine();
        $this->info("📊 Jobs in database after scraping: {$afterCount}");
        $this->info("📈 New jobs added: " . ($afterCount - $beforeCount));
        
        // Show sample jobs
        if ($afterCount > 0) {
            $this->newLine();
            $this->info('📋 Sample of latest jobs:');
            $this->newLine();
            
            $sampleJobs = Job::latest()->take(5)->get(['title', 'company', 'source', 'created_at']);
            
            $this->table(
                ['Title', 'Company', 'Source', 'Added'],
                $sampleJobs->map(function($job) {
                    return [
                        substr($job->title, 0, 40),
                        substr($job->company, 0, 20),
                        $job->source,
                        $job->created_at->diffForHumans()
                    ];
                })->toArray()
            );
        }
        
        $this->newLine();
        $this->info('✨ Test completed successfully!');
        
        return 0;
    }
}
