<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentorship_id',
        'appointment_id',
        'course_id',
        'from_user_id',
        'to_user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function mentorship()
    {
        return $this->belongsTo(Mentorship::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
