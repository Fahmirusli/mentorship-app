<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'title',
        'description',
        'price',
        'tags',
        'syllabus',
    ];

    protected $casts = [
        'tags' => 'array',
        'syllabus' => 'array',
    ];

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
