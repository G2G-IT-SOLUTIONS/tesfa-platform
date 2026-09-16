<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketMetric extends Model
{
    protected $fillable = [
        'neighborhood','sub_city','property_type','metric_date',
        'avg_price_per_sqm','median_price_per_sqm','min_price_per_sqm',
        'max_price_per_sqm','price_change_30d','price_change_90d',
        'total_listings','active_listings','new_listings_7d','sold_listings_7d',
        'total_views','total_inquiries','total_saves','avg_days_on_market',
        'demand_score','avg_rental_yield','avg_monthly_rent',
    ];

    protected $casts = [
        'metric_date' => 'date',
    ];
}