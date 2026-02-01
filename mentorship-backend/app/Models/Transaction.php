<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_code',
        'user_id',
        'appointment_id',
        'amount',
        'status',
        'payment_provider',
        'payment_metadata',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
