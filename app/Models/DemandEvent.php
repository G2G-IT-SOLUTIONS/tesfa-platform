<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandEvent extends Model
{
    protected $fillable = [
        'event_name','event_type','start_date','end_date','affected_neighborhoods',
        'expected_demand_multiplier','actual_multiplier','is_recurring',
        'recurrence_pattern','description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'affected_neighborhoods' => 'array',
        'is_recurring' => 'boolean',
    ];
}