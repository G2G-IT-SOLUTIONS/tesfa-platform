<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::where('email', 'seller@tesfa.et')->first();
        $agent  = User::where('email', 'agent@tesfa.et')->first();

        if (!$seller) {
            $this->command->warn('Seller user not found. Run UserSeeder first.');
            return;
        }

        $properties = [
            [
                'title' => 'Modern 3-Bedroom Apartment in Bole',
                'description' => 'Spacious modern apartment with city views, fully furnished kitchen, 24/7 security.',
                'type' => 'apartment',
                'purpose' => 'sale',
                'price' => 12000000,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area_sqm' => 145,
                'condition' => 'excellent',
                'address' => 'Bole Medhanealem, near Edna Mall',
                'neighborhood' => 'Bole',
                'city' => 'Addis Ababa',
                'location' => new Point(9.0108, 38.7613),
            ],
            [
                'title' => 'Luxury Villa in CMC',
                'description' => 'Standalone villa with garden, garage, and modern finishes.',
                'type' => 'villa',
                'purpose' => 'sale',
                'price' => 35000000,
                'bedrooms' => 5,
                'bathrooms' => 4,
                'area_sqm' => 380,
                'condition' => 'new',
                'address' => 'CMC Summit, behind Total Station',
                'neighborhood' => 'CMC',
                'city' => 'Addis Ababa',
                'location' => new Point(9.0227, 38.8226),
            ],
            [
                'title' => 'Cozy 2-Bedroom Rental in Sarbet',
                'description' => 'Well-maintained apartment ideal for small families.',
                'type' => 'apartment',
                'purpose' => 'rent',
                'price' => 35000,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'area_sqm' => 95,
                'condition' => 'good',
                'address' => 'Sarbet, near Atlas',
                'neighborhood' => 'Sarbet',
                'city' => 'Addis Ababa',
                'location' => new Point(9.0106, 38.7322),
            ],
        ];

        foreach ($properties as $data) {
            $data['owner_id'] = $seller->id;
            $data['agent_id'] = $agent?->id;
            $data['slug'] = Str::slug($data['title']) . '-' . uniqid();
            $data['verification_status'] = 'verified';
            $data['status'] = 'active';
            $data['published_at'] = now();

            Property::create($data);
        }

        $this->command->info('Created ' . count($properties) . ' sample properties.');
    }
}