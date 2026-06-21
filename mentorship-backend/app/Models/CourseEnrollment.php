<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentee_id',
        'course_id',
        'progress_percent',
        'completed_tasks',
        'status',
    ];

    protected $casts = [
        'completed_tasks' => 'array',
    ];

    public function mentee()
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
