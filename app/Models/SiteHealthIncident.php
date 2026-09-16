<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteHealthIncident extends Model
{
    protected $fillable = [
        'incident_type','severity','description','affected_metrics',
        'start_time','end_time','duration_minutes','root_cause','resolution',
        'lessons_learned',
    ];

    protected $casts = [
        'affected_metrics' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}