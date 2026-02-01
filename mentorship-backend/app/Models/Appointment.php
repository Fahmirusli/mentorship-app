<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'mentee_id',
        'mentorship_id',
        'appointment_date',
        'appointment_time',
        'scheduled_at',
        'duration_minutes',
        'status',
        'meeting_link',
        'notes',
        'fee',
        'payment_status',
        'bill_code',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'appointment_date' => 'date',
    ];

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentee()
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    public function mentorship()
    {
        return $this->belongsTo(Mentorship::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}