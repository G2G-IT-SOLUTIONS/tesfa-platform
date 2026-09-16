<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AVMValuation extends Model
{
    protected $table = 'avm_valuations';
    protected $fillable = [
        'property_id','estimated_value','confidence_score','value_range_low',
        'value_range_high','model_version','features_used','feature_importance',
        'comparable_properties','market_conditions','validation_status',
        'actual_sale_price','accuracy_pct','computed_at','validated_at','validated_by',
    ];

    protected $casts = [
        'features_used' => 'array',
        'feature_importance' => 'array',
        'comparable_properties' => 'array',
        'market_conditions' => 'array',
        'confidence_score' => 'decimal:4',
        'computed_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }
}