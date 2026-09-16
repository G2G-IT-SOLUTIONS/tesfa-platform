<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBehaviorLog extends Model
{
    protected $fillable = [
        'user_id','session_id','event_type','event_name','page_url','page_path',
        'referrer','property_id','property_type','property_price',
        'property_neighborhood','search_query','search_filters',
        'search_results_count','element_id','element_text','element_type',
        'time_on_page','time_on_element','scroll_depth','device_type','browser',
        'os','screen_resolution','ip_address','country','city','conversion_value',
        'conversion_type','engagement_score','intent_score','churn_risk_score',
    ];

    protected $casts = [
        'search_filters' => 'array',
    ];

    public $timestamps = false;

    protected $dates = ['created_at'];

    public function user() { return $this->belongsTo(User::class); }
    public function property() { return $this->belongsTo(Property::class); }
}