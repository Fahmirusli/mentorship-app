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
        'bio',
        'skills',
        'interests',
        'profile_image',
        'is_active',
        'is_verified',
        'verified_at',
        'google_id',
        'github_id',
        'linkedin_id',
        'avatar',
        'telegram_chat_id',
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
}