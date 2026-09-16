<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ABTestResult extends Model
{
    protected $table = 'ab_test_results';
    protected $fillable = [
        'test_id','test_name','description','variant_a_name','variant_b_name',
        'variant_a_config','variant_b_config','metric_name','variant_a_value',
        'variant_b_value','lift_pct','p_value','is_significant',
        'variant_a_samples','variant_b_samples','status','winner',
        'started_at','completed_at',
    ];

    protected $casts = [
        'variant_a_config' => 'array',
        'variant_b_config' => 'array',
        'is_significant' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}