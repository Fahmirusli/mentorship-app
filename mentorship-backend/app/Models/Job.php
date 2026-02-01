<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'company',
        'description',
        'requirements',
        'location',
        'salary',
        'source',
        'external_url',
        'posted_date',
        'is_active',
        // Legacy/Alternate names to support different DB schemas
        'job_type',
        'experience_level',
        'salary_range',
        'source_platform',
        'source_url',
        'required_skills',
    ];

    protected $casts = [
        'requirements' => 'array',
        'posted_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getRequirementsArrayAttribute()
    {
        return is_string($this->requirements) 
            ? json_decode($this->requirements, true) 
            : $this->requirements;
    }

}