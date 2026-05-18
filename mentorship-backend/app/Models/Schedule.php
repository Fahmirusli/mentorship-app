<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'fee',
        'total_slots',
        'booked_slots',
    ];

    protected $casts = [
        'date' => 'date',
        'day_of_week' => 'integer',
        'is_available' => 'boolean',
        'fee' => 'decimal:2',
        'total_slots' => 'integer',
        'booked_slots' => 'integer',
    ];

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function isFullyBooked(): bool
    {
        return $this->booked_slots >= $this->total_slots;
    }
}