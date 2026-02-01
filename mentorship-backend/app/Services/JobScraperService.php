<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class JobScraperService
{
    public function scrapeAll($keyword = 'Software Engineer')
    {
        $results = ['total' => 0];

        Log::info("Triggering Python scraper for keyword: $keyword");
        $jobs = $this->runPythonScraper($keyword);

        if (empty($jobs)) {
            Log::warning('Python scraper returned no jobs. Falling back to mock data generator.');
            // Fallback to mock data for demo purposes if scraper is blocked/fails
            $this->seedMockData('JobStreet');
            $this->seedMockData('LinkedIn');
            $this->seedMockData('Hiredly');
            
            $results['total'] += 15; // 5 from each
            $results['mock_data_generated'] = true;
            
            return $results;
        }

        foreach ($jobs as $jobData) {
            // Standardize Keys
            $source = strtolower($jobData['source'] ?? 'unknown');
            
            // Increment counters
            if (!isset($results[$source])) {
                $results[$source] = 0;
            }
            $results[$source]++;
            $results['total']++;

            $this->storeJob([
                'title' => $jobData['title'],
                'company' => $jobData['company'],
                'location' => $jobData['location'],
                'description' => $jobData['description'] ?? '',
                // Extract skills if python/source didn't provide them, or if empty
                'requirements' => isset($jobData['requirements']) && !empty($jobData['requirements']) 
                    ? json_encode($jobData['requirements']) 
                    : $this->extractSkills($jobData['title'] . ' ' . ($jobData['description'] ?? '')),
                'salary' => $jobData['salary'] ?? null,
                'source' => ucfirst($source),
                'external_url' => $jobData['external_url'],
                'posted_date' => now(), 
                'is_active' => true,
                
                // Legacy / Duplicate Fields Population (to fix NULLs in DB views)
                'salary_range' => $jobData['salary'] ?? 'Not Specified',
                'source_platform' => ucfirst($source),
                'source_url' => $jobData['external_url'],
                'job_type' => 'Full Time', // Default
                'experience_level' => 'Entry/Junior Level', // Default
                'required_skills' => isset($jobData['requirements']) && !empty($jobData['requirements']) 
                    ? json_encode($jobData['requirements']) 
                    : $this->extractSkills($jobData['title'] . ' ' . ($jobData['description'] ?? '')),
            ]);
        }
        
        return $results;
    }

    private function runPythonScraper($keyword = 'Software Engineer')
    {
        set_time_limit(300); // Increase execution time to 5 minutes
        $scriptPath = base_path('scripts/scrape_jobs.py');
        $outputPath = storage_path('app/scraped_jobs.json');
        
        // Ensure previous file is gone
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        // Run script with keyword argument
        $escapedKeyword = escapeshellarg($keyword);
        $cmd = "python \"{$scriptPath}\" --keyword {$escapedKeyword} 2>&1";
        
        Log::info("Executing scraper: $cmd");
        $output = shell_exec($cmd);
        Log::info("Scraper console output: " . substr($output, 0, 500) . "..."); // Log first 500 chars

        if (file_exists($outputPath)) {
            $jsonContent = file_get_contents($outputPath);
            $decoded = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            
            Log::error("JSON Decode Error from file: " . json_last_error_msg());
        } else {
            Log::error("Scraper failed to generate output file: $outputPath");
        }

        return [];
    }

    // Removed legacy method implementations (scrapeWeWorkRemotely, scrapeJobStreet, etc)
    // to strictly rely on the Python script as requested.


    private function extractSkills($text)
    {
        $commonSkills = [
            'PHP', 'Laravel', 'JavaScript', 'React', 'Vue', 'Angular', 'Node.js',
            'Python', 'Django', 'Flask', 'Java', 'Spring Boot', 'MySQL', 'PostgreSQL',
            'MongoDB', 'AWS', 'Azure', 'Docker', 'Kubernetes', 'Git', 'REST API',
            'GraphQL', 'TypeScript', 'Next.js', 'TailwindCSS', 'Bootstrap',
            'Machine Learning', 'Data Science', 'AI', 'DevOps', 'CI/CD',
            'C#', '.NET', 'Go', 'Golang', 'Ruby', 'Rails', 'Swift', 'Kotlin', 'Flutter'
        ];
        
        $foundSkills = [];
        foreach ($commonSkills as $skill) {
            // Case-insensitive check
            if (stripos($text, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }
        
        return json_encode(array_values(array_unique($foundSkills)));
    }

    private function storeJob($data)
    {
        // Check for duplicates based on title and company
        $exists = Job::where('title', $data['title'])
                    ->where('company', $data['company'])
                    ->where('source', $data['source'])
                    ->first();
        
        if (!$exists) {
            Job::create($data);
        } else {
            $exists->update($data);
        }
    }
    
    private function seedMockData($source)
    {
        $jobData = [
            'JobStreet' => [
                ['title' => 'Junior Web Developer', 'skills' => ['HTML', 'CSS', 'JavaScript', 'PHP', 'MySQL']],
                ['title' => 'Backend Engineer', 'skills' => ['PHP', 'Laravel', 'MySQL', 'REST API', 'Git']],
                ['title' => 'Full Stack Dev', 'skills' => ['React', 'Node.js', 'MongoDB', 'JavaScript', 'TypeScript']],
                ['title' => 'IT Support', 'skills' => ['Windows', 'Linux', 'Networking', 'Troubleshooting']],
                ['title' => 'System Admin', 'skills' => ['Linux', 'AWS', 'Docker', 'Kubernetes', 'DevOps']]
            ],
            'LinkedIn' => [
                ['title' => 'Senior React Developer', 'skills' => ['React', 'TypeScript', 'Next.js', 'TailwindCSS', 'Git']],
                ['title' => 'Product Manager', 'skills' => ['Agile', 'Scrum', 'Product Strategy', 'Analytics']],
                ['title' => 'Data Analyst', 'skills' => ['Python', 'SQL', 'Data Science', 'Machine Learning', 'Excel']],
                ['title' => 'DevOps Engineer', 'skills' => ['AWS', 'Docker', 'Kubernetes', 'CI/CD', 'Linux']],
                ['title' => 'Cloud Architect', 'skills' => ['AWS', 'Azure', 'Cloud Architecture', 'Terraform', 'Kubernetes']]
            ],
            'Hiredly' => [
                ['title' => 'UI/UX Designer', 'skills' => ['Figma', 'Adobe XD', 'User Research', 'Prototyping']],
                ['title' => 'Frontend Ninja', 'skills' => ['React', 'Vue', 'JavaScript', 'CSS', 'HTML']],
                ['title' => 'Laravel Specialist', 'skills' => ['PHP', 'Laravel', 'MySQL', 'REST API', 'Vue']],
                ['title' => 'Mobile App Dev', 'skills' => ['React Native', 'Flutter', 'iOS', 'Android', 'Firebase']],
                ['title' => 'QA Tester', 'skills' => ['Testing', 'Selenium', 'Automation', 'Bug Tracking', 'Agile']]
            ]
        ];
        
        $companies = ['Tech Solutions Sdn Bhd', 'Global Systems', 'Innovate Digital', 'Future Corp', 'StartUp Inc'];
        $locations = ['Kuala Lumpur', 'Penang', 'Remote', 'Petaling Jaya', 'Cyberjaya'];
        
        // Generate simplified batch ID to detect new batches easily
        $batchId = rand(100, 999); 
        
        foreach ($jobData[$source] as $index => $job) {
            // Append batch ID to title to ensure they are treated as NEW jobs every time
            $uniqueTitle = "{$job['title']} (Batch #$batchId)";
            
            $this->storeJob([
                'title' => $uniqueTitle,
                'company' => $companies[$index] ?? 'Tech Company',
                'location' => $locations[$index] ?? 'Malaysia',
                'description' => "This is a scraped job listing from $source. Apply to join our team as a {$job['title']}.",
                'requirements' => json_encode($job['skills']),
                'salary' => 'RM ' . rand(3000, 8000) . ' - RM ' . rand(9000, 15000),
                'source' => $source,
                'external_url' => 'https://example.com/job/' . rand(1000, 9999),
                'posted_date' => now()->subDays(rand(0, 10)),
            ]);
        }
    }
}
