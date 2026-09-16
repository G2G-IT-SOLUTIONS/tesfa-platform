<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBehaviorSummary extends Model
{
    protected $fillable = [
        'user_id','summary_date','sessions_count','avg_session_duration',
        'page_views_total','unique_pages_viewed','searches_count',
        'avg_search_results','properties_viewed','properties_saved',
        'properties_shared','properties_inquired','engagement_score',
        'intent_score','churn_risk_score','funnel_stage',
        'preferred_neighborhoods','preferred_property_types',
        'price_range_min','price_range_max',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'preferred_neighborhoods' => 'array',
        'preferred_property_types' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
}