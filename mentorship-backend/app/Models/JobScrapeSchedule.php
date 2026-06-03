<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobScrapeSchedule extends Model
{
    protected $fillable = [
        'run_time',
        'timezone',
        'keyword',
        'enabled',
        'last_run_at',
        'last_run_status',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
    ];
}
