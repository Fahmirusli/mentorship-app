<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Process;
class JobScraperService
{
    private const SOURCE_CANONICAL_NAMES = [
        'linkedin' => 'LinkedIn',
        'jobstreet' => 'JobStreet',
        'maukerja' => 'MauKerja',
    ];

    public function scrapeAll($keyword = 'Software Engineer')
    {
        $results = ['total' => 0];

        Log::info("Attempting to scrape jobs using Python script for keyword: {$keyword}");
        $jobs = $this->fetchPythonScraperJobs($keyword);

        if (empty($jobs)) {
            Log::warning('Python scraper failed or returned no jobs. Falling back to RapidAPI.');
            $jobs = $this->fetchRapidApiJobs($keyword);
        }

        if (empty($jobs)) {
            Log::warning('RapidAPI returned no jobs or is not configured.');
            return $results;
        }

        foreach ($jobs as $jobData) {
            // Standardize Keys
            $sourceName = $jobData['source'] ?? 'Unknown';
            $sourceKey = strtolower(preg_replace('/[^a-z0-9]/', '', $sourceName));
            
            // Increment counters
            if (!isset($results[$sourceKey])) {
                $results[$sourceKey] = 0;
            }
            $results[$sourceKey]++;
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
                'source' => $sourceName,
                'external_url' => $jobData['external_url'],
                'posted_date' => now(), 
                'is_active' => true,
                
                // Legacy / Duplicate Fields Population (to fix NULLs in DB views)
                'salary_range' => $jobData['salary'] ?? 'Not Specified',
                'source_platform' => $sourceName,
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

    private function fetchPythonScraperJobs(string $keyword): array
    {
        try {
            $scriptPath = base_path('scripts/scrape_jobs.py');
            
            if (!file_exists($scriptPath)) {
                Log::error("Python scraper script not found at: {$scriptPath}");
                return [];
            }

            $process = new Process(['python', $scriptPath, '--keyword', $keyword]);
            $process->setTimeout(180); // 3 minutes timeout for Selenium
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Python scraper process failed', [
                    'error' => $process->getErrorOutput()
                ]);
                return [];
            }

            $output = $process->getOutput();
            // Find JSON array in the output (since python script might print other stuff to stdout)
            $start = strpos($output, '[');
            $end = strrpos($output, ']');
            if ($start !== false && $end !== false) {
                $jsonStr = substr($output, $start, $end - $start + 1);
                $jobs = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($jobs)) {
                    return $jobs;
                }
            }

            Log::error('Failed to decode JSON from Python scraper', [
                'output' => substr($output, 0, 500)
            ]);
            return [];

        } catch (\Exception $e) {
            Log::error('Exception while running Python scraper', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function fetchRapidApiJobs(string $keyword): array
    {
        $apiKey = config('services.rapidapi.key');
        $apiHost = config('services.rapidapi.host', 'jsearch.p.rapidapi.com');
        $numPages = (int) config('services.rapidapi.num_pages', 3);
        $datePosted = config('services.rapidapi.date_posted', 'week');
        $country = config('services.rapidapi.country', 'my'); // Default to 'my' if not set in config
        $allowedSources = config('services.rapidapi.allowed_sources', [
            'linkedin',
            'jobstreet',
            'maukerja',
        ]);
        $allowedSources = array_map('strtolower', $allowedSources);
        $allowedSourcesNormalized = array_values(array_filter(array_map(
            fn($source) => preg_replace('/[^a-z0-9]/', '', $source),
            $allowedSources
        )));

        if ($numPages < 1) {
            $numPages = 1;
        }

        if (!$apiKey) {
            Log::warning('RAPIDAPI_KEY is not configured.');
            return [];
        }

        $endpoint = "https://{$apiHost}/search";
        $queries = array_values(array_unique(array_merge(
            [$keyword],
            array_map(fn($allowed) => $keyword . ' ' . (self::SOURCE_CANONICAL_NAMES[$allowed] ?? $allowed), $allowedSourcesNormalized)
        )));
        $items = [];

        foreach ($queries as $query) {
            $queryParams = [
                'query' => $query,
                'page' => 1,
                'num_pages' => $numPages,
                'date_posted' => $datePosted,
            ];

            if (!empty($country)) {
                $queryParams['country'] = $country;
            }

                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                                'X-RapidAPI-Key' => $apiKey,
                                'X-RapidAPI-Host' => $apiHost,
                        ])->connectTimeout(20)
                            ->timeout(60)
                            ->retry(1, 500)
                            ->get($endpoint, $queryParams);

            if (!$response->ok()) {
                Log::warning('RapidAPI request failed', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                continue;
            }

            $payload = $response->json();
            $items = array_merge($items, $payload['data'] ?? []);
        }

        if (empty($items)) {
            return [];
        }

        $jobs = [];
        $seen = [];

        foreach ($items as $item) {
            $sourceRaw = $item['job_publisher']
                ?? $item['job_offer_source']
                ?? ($item['job_board'] ?? 'RapidAPI');
            $sourceNormalized = preg_replace('/[^a-z0-9]/', '', strtolower($sourceRaw));
            $matchedAllowedSource = null;

            foreach ($allowedSourcesNormalized as $allowed) {
                if ($sourceNormalized === $allowed
                    || str_contains($sourceNormalized, $allowed)
                    || str_contains($allowed, $sourceNormalized)
                ) {
                    $matchedAllowedSource = $allowed;
                    break;
                }
            }

            if (!$matchedAllowedSource) {
                continue;
            }

            $canonicalSource = self::SOURCE_CANONICAL_NAMES[$matchedAllowedSource] ?? $sourceRaw;
            $externalUrl = $item['job_apply_link'] ?? ($item['job_apply_is_direct'] ? ($item['job_apply_link'] ?? '') : ($item['job_google_link'] ?? ''));
            $uniqueKey = strtolower(($item['job_title'] ?? 'untitled') . '|' . ($item['employer_name'] ?? 'unknown') . '|' . $externalUrl);

            if (isset($seen[$uniqueKey])) {
                continue;
            }

            $seen[$uniqueKey] = true;

            $jobs[] = [
                'title' => $item['job_title'] ?? 'Untitled',
                'company' => $item['employer_name'] ?? 'Unknown',
                'location' => $item['job_city'] ?? ($item['job_country'] ?? 'Remote'),
                'description' => $item['job_description'] ?? '',
                'requirements' => $item['job_required_skills'] ?? [],
                'salary' => $item['job_salary_currency'] ?? null,
                'source' => $canonicalSource,
                'external_url' => $externalUrl,
            ];
        }

        return $jobs;
    }


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
            'MauKerja' => [
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
