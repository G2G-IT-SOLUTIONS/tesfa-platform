<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIPrediction extends Model
{
    protected $table = 'ai_predictions';
    protected $fillable = [
        'prediction_type','target_type','target_id','target_name',
        'predicted_value','predicted_value_min','predicted_value_max','confidence',
        'features_used','feature_importance','actual_value','accuracy',
        'model_version','model_type','training_data_start','training_data_end',
        'training_samples','prediction_for_date','computed_at','expires_at','status',
    ];

    protected $casts = [
        'features_used' => 'array',
        'feature_importance' => 'array',
        'training_data_start' => 'date',
        'training_data_end' => 'date',
        'prediction_for_date' => 'date',
        'computed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}