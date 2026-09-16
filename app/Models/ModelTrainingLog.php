<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelTrainingLog extends Model
{
    protected $fillable = [
        'run_id','model_name','model_version','data_version','hyperparameters',
        'training_data_start','training_data_end','training_samples',
        'duration_seconds','status','error_message','final_accuracy','final_loss',
        'started_at','completed_at',
    ];

    protected $casts = [
        'hyperparameters' => 'array',
        'training_data_start' => 'date',
        'training_data_end' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}