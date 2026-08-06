<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class JobMatchingService
{
    public function getRecommendations($userId)
    {
        $user = User::with('menteeProfile')->find($userId);
        
        if (!$user) {
            return [];
        }
        
        $profileSkills = [];
        if ($user->menteeProfile) {
            $profileSkills = $user->menteeProfile->current_skills ?? [];
            if (is_string($profileSkills)) {
                $profileSkills = json_decode($profileSkills, true) ?? [];
            }
        }
        
        // Strictly use Current Skills Detail (Mentee Profile)
        $userSkills = array_values(array_unique($profileSkills));

        if (empty($userSkills)) {
            return Job::latest()->take(50)->get();
        }
        
        $jobs = Job::orderBy('posted_date', 'desc')->get();
        $recommendations = [];
        
        foreach ($jobs as $job) {
            // Handle both string and array formats for requirements
            $jobRequirements = $job->requirements;
            if (is_string($jobRequirements)) {
                $jobRequirements = json_decode($jobRequirements ?? '[]', true);
            }
            if (!is_array($jobRequirements)) {
                $jobRequirements = [];
            }
            
            $matchScore = $this->calculateMatchScore($userSkills, $jobRequirements);
            $missingSkills = $this->calculateMissingSkills($userSkills, $jobRequirements);
            $skillGap = count($missingSkills);
            
            $recommendations[] = [
                'job' => $job,
                'match_score' => $matchScore,
                'skill_gap' => $skillGap,
                'missing_skills' => $missingSkills
            ];
        }
        
        // Sort by match score, then by latest posted date
        usort($recommendations, function($a, $b) {
            if ($a['match_score'] == $b['match_score']) {
                $dateA = $a['job']->posted_date ? strtotime($a['job']->posted_date) : 0;
                $dateB = $b['job']->posted_date ? strtotime($b['job']->posted_date) : 0;
                return $dateB <=> $dateA;
            }
            return $b['match_score'] <=> $a['match_score'];
        });
        
        return array_slice($recommendations, 0, 50);
    }
    
    public function calculateMatchScore($userSkills, $jobRequirements)
    {
        if (empty($jobRequirements)) {
            return 0;
        }

        $userSkillsNorm = array_map(fn($s) => trim(strtolower($s)), $userSkills);
        $jobReqsNorm = array_map(fn($s) => trim(strtolower($s)), $jobRequirements);
        
        // Step 1: Map user skills to job requirements using fuzzy logic
        // This ensures if a user has "react.js" and job requires "react", 
        // the user skill is transformed to "react" for vocabulary matching.
        $mappedUserSkills = [];
        foreach ($userSkillsNorm as $uSkill) {
            $mapped = $uSkill; // Default to original if no match
            foreach ($jobReqsNorm as $req) {
                if ($uSkill === $req || ($uSkill !== '' && $req !== '' && (str_contains($uSkill, $req) || str_contains($req, $uSkill)))) {
                    $mapped = $req; // Map to the exact job requirement term
                    break;
                }
            }
            $mappedUserSkills[] = $mapped;
        }
        
        // 1. Build Vocabulary - Strictly based on Job Requirements
        // This prevents heavily penalizing users who have many extra skills not related to the job
        $vocabulary = array_unique($jobReqsNorm);
        
        if (empty($vocabulary)) {
            return 0;
        }

        // 2. Term Frequency (TF) - Use Boolean Frequency (1 if present, 0 if missing)
        $userTf = array_fill_keys($vocabulary, 0);
        $jobTf = array_fill_keys($vocabulary, 0);
        
        foreach ($mappedUserSkills as $term) {
            if (isset($userTf[$term])) {
                $userTf[$term] = 1;
            }
        }

        foreach ($jobReqsNorm as $term) {
            if (isset($jobTf[$term])) {
                $jobTf[$term] = 1;
            }
        }

        // 3. Inverse Document Frequency (IDF)
        $N = 2; // Two documents: User and Job
        $idf = [];
        foreach ($vocabulary as $term) {
            $docCount = 0;
            if (in_array($term, $mappedUserSkills)) $docCount++;
            if (in_array($term, $jobReqsNorm)) $docCount++;
            
            $idf[$term] = log((1 + $N) / (1 + $docCount)) + 1; 
        }

        // 4. TF-IDF Vectors
        $userVector = [];
        $jobVector = [];
        $dotProduct = 0;
        $userMagnitudeSq = 0;
        $jobMagnitudeSq = 0;

        foreach ($vocabulary as $term) {
            $userVector[$term] = $userTf[$term] * $idf[$term];
            $jobVector[$term] = $jobTf[$term] * $idf[$term];
            
            $dotProduct += $userVector[$term] * $jobVector[$term];
            $userMagnitudeSq += pow($userVector[$term], 2);
            $jobMagnitudeSq += pow($jobVector[$term], 2);
        }

        $userMagnitude = sqrt($userMagnitudeSq);
        $jobMagnitude = sqrt($jobMagnitudeSq);

        if ($userMagnitude == 0 || $jobMagnitude == 0) {
            return 0;
        }

        $cosineSimilarity = $dotProduct / ($userMagnitude * $jobMagnitude);
        
        return round($cosineSimilarity * 100, 2);
    }
    
    private function calculateMissingSkills($userSkills, $jobRequirements)
    {
        $userSkillsNorm = array_map(fn($s) => trim(strtolower($s)), $userSkills);
        $missingSkills = [];

        foreach ($jobRequirements as $req) {
            $reqNorm = trim(strtolower($req));
            if (in_array($reqNorm, $userSkillsNorm)) {
                continue;
            }

            $found = false;
            foreach ($userSkillsNorm as $uSkill) {
                if ($uSkill === $reqNorm || ($uSkill !== '' && $reqNorm !== '' && (str_contains($uSkill, $reqNorm) || str_contains($reqNorm, $uSkill)))) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $missingSkills[] = $req;
            }
        }
        
        return array_values(array_unique($missingSkills));
    }
    
    public function analyzeJobMatch($userId, $jobId)
    {
        $user = User::with('menteeProfile')->find($userId);
        $job = Job::find($jobId);
        
        if (!$user || !$job) {
            return null;
        }
        
        $profileSkills = [];
        if ($user->menteeProfile) {
            $profileSkills = $user->menteeProfile->current_skills ?? [];
            if (is_string($profileSkills)) {
                $profileSkills = json_decode($profileSkills, true) ?? [];
            }
        }
        
        // Strictly use Current Skills Detail (Mentee Profile)
        $userSkills = array_values(array_unique($profileSkills));
        
        // Get job requirements
        $jobRequirements = is_string($job->requirements) 
            ? json_decode($job->requirements, true) 
            : $job->requirements;
            
        $jobRequirements = $jobRequirements ?: [];
        
        // If job has no requirements, return basic analysis
        if (empty($jobRequirements)) {
            return [
                'match_score' => 0,
                'matching_skills' => [],
                'missing_skills' => [],
                'skill_gap' => 0,
                'total_requirements' => 0,
                'user_has' => 0,
                'recommendations' => []
            ];
        }
        
        // Normalize: lowercase and trim
        $userSkillsNorm = array_map(fn($s) => trim(strtolower($s)), $userSkills);
        $jobReqsNorm = array_map(fn($s) => trim(strtolower($s)), $jobRequirements);

        Log::info("Job Analysis Debug", ['user' => $userSkillsNorm, 'job' => $jobReqsNorm]);

        $missingSkills = $this->calculateMissingSkills($userSkills, $jobRequirements);
        
        // Find matching skills by diffing jobRequirements against missingSkills
        $matchingSkills = [];
        foreach ($jobRequirements as $req) {
            if (!in_array($req, $missingSkills)) {
                $matchingSkills[] = $req;
            }
        }
        
        $matchScore = count($jobRequirements) > 0 
            ? (count($matchingSkills) / count($jobRequirements)) * 100 
            : 0;

        return [
            'match_score' => round($matchScore, 2),
            'matching_skills' => array_values(array_unique($matchingSkills)),
            'missing_skills' => $missingSkills,
            'skill_gap' => count($missingSkills),
            'total_requirements' => count($jobRequirements),
            'user_has' => count($matchingSkills),
            'recommendations' => $this->getSkillRecommendations($missingSkills)
        ];
    }
    
    private function getSkillRecommendations($skills)
    {
        $courses = [
            'React' => ['name' => 'React - The Complete Guide', 'url' => 'https://www.udemy.com/course/react-the-complete-guide/'],
            'Node.js' => ['name' => 'Node.js, Express, MongoDB & More', 'url' => 'https://www.udemy.com/course/nodejs-express-mongodb-bootcamp/'],
            'Python' => ['name' => 'Complete Python Bootcamp', 'url' => 'https://www.udemy.com/course/complete-python-bootcamp/'],
            'AWS' => ['name' => 'AWS Certified Solutions Architect', 'url' => 'https://www.udemy.com/course/aws-certified-solutions-architect-associate/'],
            'Docker' => ['name' => 'Docker Mastery', 'url' => 'https://www.udemy.com/course/docker-mastery/'],
        ];
        
        $recommendations = [];
        foreach ($skills as $skill) {
            if (isset($courses[$skill])) {
                $recommendations[] = array_merge(['skill' => $skill], $courses[$skill]);
            }
        }
        
        return $recommendations;
    }
}