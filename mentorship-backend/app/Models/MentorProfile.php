<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MentorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'expertise_areas',
        'industry',
        'job_title',
        'company',
        'years_of_experience',
        'mentorship_approach',
        'is_available',
        'rating',
        'total_mentees',
    ];

    protected $casts = [
        'expertise_areas' => 'array',
        'is_available' => 'boolean',
        'rating' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}