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

        $missingSkills = $this->calculateMissingSkills($userSkills, $jobRequirements);
        $totalRequirements = count($jobRequirements);
        
        if ($totalRequirements === 0) {
            return 0;
        }
        
        $matchingCount = $totalRequirements - count($missingSkills);
        $matchScore = ($matchingCount / $totalRequirements) * 100;
        
        return round($matchScore, 2);
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