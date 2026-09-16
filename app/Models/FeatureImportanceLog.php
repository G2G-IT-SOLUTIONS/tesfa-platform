<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureImportanceLog extends Model
{
    protected $fillable = [
        'model_name','model_version','feature_name','importance_score','rank',
        'computed_date',
    ];

    protected $casts = [
        'computed_date' => 'date',
    ];
}