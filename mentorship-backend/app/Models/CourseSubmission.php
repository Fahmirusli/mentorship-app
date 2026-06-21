<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_enrollment_id',
        'task_index',
        'file_url',
        'link',
        'notes',
        'status',
        'mentor_feedback',
    ];

    public function enrollment()
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
