<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicPricingRecommendation extends Model
{
    protected $fillable = [
        'property_id','current_rate','recommended_rate','rate_change_pct',
        'potential_revenue_increase','base_rate','demand_multiplier',
        'event_multiplier','competitor_adjustment','day_of_week_multiplier',
        'seasonal_multiplier','pricing_date','days_until_event','event_name',
        'competitor_rates_avg','competitor_rates_count','host_action',
        'host_action_at','host_action_reason','actual_bookings','actual_revenue',
        'vs_manual_pricing_revenue','model_version','computed_at','expires_at',
    ];

    protected $casts = [
        'pricing_date' => 'date',
        'host_action_at' => 'datetime',
        'computed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }
}