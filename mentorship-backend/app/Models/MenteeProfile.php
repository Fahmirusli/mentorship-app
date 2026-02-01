<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenteeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_skills',
        'skills_to_learn',
        'career_goals',
        'education_level',
        'field_of_study',
    ];

    protected $casts = [
        'current_skills' => 'array',
        'skills_to_learn' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}