<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'bio',
        'skills',
        'interests',
        'profile_image',
        'resume_path',
        'points',
        'is_active',
        'is_verified',
        'verified_at',
        'google_id',
        'github_id',
        'avatar',
        'wallet_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'skills' => 'array',
        'interests' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Accessors
    public function getAverageRatingAttribute()
    {
        // If there is no feedback, default to 5.0 stars as requested
        if ($this->feedbackReceived()->count() === 0) {
            return 5.0;
        }
        
        // Calculate average and round to 1 decimal place
        return round($this->feedbackReceived()->avg('rating'), 1);
    }

    // Relationships
    public function mentorProfile()
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function menteeProfile()
    {
        return $this->hasOne(MenteeProfile::class);
    }

    public function mentorships()
    {
        return $this->hasMany(Mentorship::class, 'mentor_id');
    }

    public function menteeMentorships()
    {
        return $this->hasMany(Mentorship::class, 'mentee_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'mentor_id');
    }

    public function feedbackGiven()
    {
        return $this->hasMany(Feedback::class, 'from_user_id');
    }

    public function feedbackReceived()
    {
        return $this->hasMany(Feedback::class, 'to_user_id');
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    // Helper methods
    public function isMentor()
    {
        return $this->role === 'mentor';
    }

    public function isMentee()
    {
        return $this->role === 'mentee';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isProfileComplete(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $hasBase = !empty($this->name)
            && !empty($this->phone)
            && !empty($this->bio)
            && !empty($this->address);

        if (!$hasBase) {
            return false;
        }

        if ($this->isMentor()) {
            $skills = is_array($this->skills) ? $this->skills : [];
            return count($skills) > 0;
        }

        if ($this->isMentee()) {
            $skills = is_array($this->skills) ? $this->skills : [];
            if (count($skills) > 0) {
                return true;
            }

            $profile = $this->menteeProfile;
            if (!$profile) {
                return false;
            }

            $currentSkills = is_array($profile->current_skills) ? $profile->current_skills : [];
            $targetSkills = is_array($profile->skills_to_learn) ? $profile->skills_to_learn : [];

            return count($currentSkills) > 0 || count($targetSkills) > 0;
        }

        return true;
    }
}