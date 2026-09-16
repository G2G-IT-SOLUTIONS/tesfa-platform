<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelPerformanceMetric extends Model
{
    protected $fillable = [
        'model_name','model_version','metric_date','accuracy','precision_score',
        'recall_score','f1_score','auc_roc','mae','rmse','mape',
        'predictions_count','validated_count','confusion_matrix','metadata',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'confusion_matrix' => 'array',
        'metadata' => 'array',
    ];
}