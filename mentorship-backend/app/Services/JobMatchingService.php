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
        
        // Fix: Fetch skills from User model first
        $userSkills = $user->skills ?? [];

        // Handle JSON string vs Array
        if (is_string($userSkills)) {
            $userSkills = json_decode($userSkills, true) ?? [];
        }

        // Fallback to Mentee Profile
        if (empty($userSkills) && $user->menteeProfile) {
            $profileSkills = $user->menteeProfile->current_skills ?? [];
            if (is_string($profileSkills)) {
                $profileSkills = json_decode($profileSkills, true) ?? [];
            }
            $userSkills = $profileSkills;
        }

        if (empty($userSkills)) {
            return Job::latest()->take(50)->get();
        }
        
        $jobs = Job::all();
        $recommendations = [];
        
        foreach ($jobs as $job) {
            $jobRequirements = json_decode($job->requirements ?? '[]', true);
            
            $matchScore = $this->calculateMatchScore($userSkills, $jobRequirements);
            $skillGap = $this->calculateSkillGap($userSkills, $jobRequirements);
            
            $recommendations[] = [
                'job' => $job,
                'match_score' => $matchScore,
                'skill_gap' => $skillGap,
                'missing_skills' => array_diff($jobRequirements, $userSkills)
            ];
        }
        
        // Sort by match score
        usort($recommendations, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        return array_slice($recommendations, 0, 50);
    }
    
    private function calculateMatchScore($userSkills, $jobRequirements)
    {
        if (empty($jobRequirements)) {
            return 0;
        }

        $userSkillsNorm = array_map(fn($s) => trim(strtolower($s)), $userSkills);
        $jobReqsNorm = array_map(fn($s) => trim(strtolower($s)), $jobRequirements);
        
        $matches = 0;
        foreach ($jobReqsNorm as $req) {
            foreach ($userSkillsNorm as $uSkill) {
                if ($uSkill === $req || (str_contains($uSkill, $req) || str_contains($req, $uSkill))) {
                    $matches++;
                    break;
                }
            }
        }

        return round(($matches / count($jobRequirements)) * 100, 2);
    }
    
    private function calculateSkillGap($userSkills, $jobRequirements)
    {
        // Simplified gap calc for listing
        return count($jobRequirements) - ($this->calculateMatchScore($userSkills, $jobRequirements) / 100 * count($jobRequirements));
    }
    
    public function analyzeJobMatch($userId, $jobId)
    {
        $user = User::with('menteeProfile')->find($userId);
        $job = Job::find($jobId);
        
        if (!$user || !$job) {
            return null;
        }
        
        // Fix: Fetch skills from User model first (where seeded data lives)
        $userSkills = $user->skills ?? [];

        // Handle JSON string vs Array (since it's cast in model, it should be array, but safe check)
        if (is_string($userSkills)) {
            $userSkills = json_decode($userSkills, true) ?? [];
        }

        // Fallback to Mentee Profile if User skills are empty
        if (empty($userSkills) && $user->menteeProfile) {
            $profileSkills = $user->menteeProfile->current_skills ?? [];
            if (is_string($profileSkills)) {
                $profileSkills = json_decode($profileSkills, true) ?? [];
            }
            $userSkills = $profileSkills;
        }
        
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

        $matchingSkills = [];
        $missingSkills = [];

        foreach ($jobRequirements as $req) {
            $reqNorm = trim(strtolower($req));
            
            // Check for exact match in normalized array
            if (in_array($reqNorm, $userSkillsNorm)) {
                $matchingSkills[] = $req;
                continue;
            }

            // Check for fuzzy/partial match
            $found = false;
            foreach ($userSkillsNorm as $uSkill) {
                if ($uSkill === $reqNorm || 
                    ($uSkill !== '' && $reqNorm !== '' && (str_contains($uSkill, $reqNorm) || str_contains($reqNorm, $uSkill)))) {
                    $matchingSkills[] = $req;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $missingSkills[] = $req;
            }
        }
        
        $matchScore = count($jobRequirements) > 0 
            ? (count($matchingSkills) / count($jobRequirements)) * 100 
            : 0;

        return [
            'match_score' => round($matchScore, 2),
            'matching_skills' => array_values(array_unique($matchingSkills)),
            'missing_skills' => array_values(array_unique($missingSkills)),
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