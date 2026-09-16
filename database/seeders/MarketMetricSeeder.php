<?php

namespace Database\Seeders;

use App\Models\MarketMetric;
use Illuminate\Database\Seeder;

class MarketMetricSeeder extends Seeder
{
    public function run(): void
    {
        $neighborhoods = ['Bole', 'CMC', 'Sarbet', 'Mekanisa', 'Kazanchis'];

        foreach ($neighborhoods as $hood) {
            MarketMetric::create([
                'neighborhood' => $hood,
                'property_type' => 'all',
                'metric_date' => today(),
                'avg_price_per_sqm' => rand(45000, 95000),
                'median_price_per_sqm' => rand(45000, 95000),
                'min_price_per_sqm' => rand(35000, 50000),
                'max_price_per_sqm' => rand(95000, 150000),
                'total_listings' => rand(20, 100),
                'active_listings' => rand(20, 100),
                'new_listings_7d' => rand(1, 10),
                'sold_listings_7d' => rand(0, 5),
                'total_views' => rand(100, 5000),
                'total_inquiries' => rand(5, 200),
                'avg_days_on_market' => rand(20, 90),
                'demand_score' => rand(40, 90),
            ]);
        }

        $this->command->info('Created market metrics for ' . count($neighborhoods) . ' neighborhoods.');
    }
}