<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandForecast extends Model
{
    protected $fillable = [
        'neighborhood','sub_city','property_type','forecast_date',
        'prediction_for_date','days_ahead','demand_index','predicted_searches',
        'predicted_inquiries','predicted_transactions','demand_index_low',
        'demand_index_high','features_used','model_version','model_type',
        'training_data_start','training_data_end','training_samples','actual_value',
        'accuracy_pct','is_validated','status','expires_at','computed_at',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'prediction_for_date' => 'date',
        'features_used' => 'array',
        'training_data_start' => 'date',
        'training_data_end' => 'date',
        'is_validated' => 'boolean',
        'expires_at' => 'datetime',
        'computed_at' => 'datetime',
    ];
}