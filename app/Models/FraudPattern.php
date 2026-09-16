<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudPattern extends Model
{
    protected $fillable = [
        'pattern_name','pattern_type','description','detection_rules',
        'hit_count','false_positive_count','is_active',
    ];

    protected $casts = [
        'detection_rules' => 'array',
        'is_active' => 'boolean',
    ];
}