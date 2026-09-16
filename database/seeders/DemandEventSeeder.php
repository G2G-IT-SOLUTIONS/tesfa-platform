<?php

namespace Database\Seeders;

use App\Models\DemandEvent;
use Illuminate\Database\Seeder;

class DemandEventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'event_name' => 'Ethiopian New Year (Enkutatash)',
                'event_type' => 'holiday',
                'start_date' => now()->addMonths(2)->startOfMonth(),
                'end_date' => now()->addMonths(2)->startOfMonth()->addDays(3),
                'affected_neighborhoods' => ['Bole', 'CMC', 'Sarbet'],
                'expected_demand_multiplier' => 1.4,
                'description' => 'Increased diaspora visits and property inquiries.',
            ],
            [
                'event_name' => 'Addis Ababa Real Estate Expo',
                'event_type' => 'summit',
                'start_date' => now()->addMonth()->startOfMonth(),
                'end_date' => now()->addMonth()->startOfMonth()->addDays(4),
                'affected_neighborhoods' => ['Bole'],
                'expected_demand_multiplier' => 1.25,
                'description' => 'Annual real estate exhibition.',
            ],
        ];

        foreach ($events as $event) {
            DemandEvent::create($event);
        }

        $this->command->info('Created ' . count($events) . ' demand events.');
    }
}