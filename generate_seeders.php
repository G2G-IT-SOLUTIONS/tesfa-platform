<?php
/**
 * Generator for Tesfa Platform seeders.
 * Run: php generate_seeders.php
 */

$seeders = [

'PropertySeeder' => <<<'SEEDER'
<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::where('email', 'seller@tesfa.et')->first();
        $agent = User::where('email', 'agent@tesfa.et')->first();

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
                'location_lat' => 9.0108,
                'location_lng' => 38.7613,
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
                'location_lat' => 9.0227,
                'location_lng' => 38.8226,
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
                'location_lat' => 9.0106,
                'location_lng' => 38.7322,
            ],
        ];

        foreach ($properties as $data) {
            $data['owner_id'] = $seller->id;
            $data['agent_id'] = $agent?->id;
            $data['slug'] = \Illuminate\Support\Str::slug($data['title']) . '-' . uniqid();
            $data['verification_status'] = 'verified';
            $data['status'] = 'active';
            $data['published_at'] = now();

            Property::create($data);
        }

        $this->command->info('Created ' . count($properties) . ' sample properties.');
    }
}
SEEDER,

'MarketMetricSeeder' => <<<'SEEDER'
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
SEEDER,

'DemandEventSeeder' => <<<'SEEDER'
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
SEEDER,

'ChatbotKnowledgeSeeder' => <<<'SEEDER'
<?php

namespace Database\Seeders;

use App\Models\ChatbotKnowledge;
use Illuminate\Database\Seeder;

class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $knowledge = [
            [
                'category' => 'faq',
                'question' => 'How does verification work?',
                'answer' => 'Every property on Tesfa undergoes physical verification by certified agents. They visit the property, verify ownership documents, and confirm the condition.',
                'keywords' => ['verify', 'verification', 'how does verification', 'trust'],
            ],
            [
                'category' => 'escrow_guide',
                'question' => 'How does escrow work?',
                'answer' => 'Our escrow service holds your funds securely until the property is verified, all documents are in order, and you confirm the transaction. There is a 14-day dispute window.',
                'keywords' => ['escrow', 'safe payment', 'secure payment', 'funds'],
            ],
            [
                'category' => 'rent_to_own_guide',
                'question' => 'What is rent-to-own?',
                'answer' => 'Rent-to-own lets you move into a property with a down payment and pay the rest in monthly installments. Your credit score determines the terms.',
                'keywords' => ['rent to own', 'installment', 'payment plan'],
            ],
            [
                'category' => 'policy',
                'question' => 'What are the platform fees?',
                'answer' => 'The platform charges a 2% fee on completed sales, with an additional 0.5% agent fee where applicable.',
                'keywords' => ['fees', 'commission', 'price', 'cost'],
            ],
        ];

        foreach ($knowledge as $item) {
            ChatbotKnowledge::create($item);
        }

        $this->command->info('Created ' . count($knowledge) . ' chatbot knowledge entries.');
    }
}
SEEDER,

'DataRetentionPolicySeeder' => <<<'SEEDER'
<?php

namespace Database\Seeders;

use App\Models\DataRetentionPolicy;
use Illuminate\Database\Seeder;

class DataRetentionPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'data_type' => 'user_behavior_logs',
                'retention_period_days' => 90,
                'legal_basis' => 'Legitimate interest - platform analytics',
                'deletion_method' => 'automatic',
                'is_active' => true,
            ],
            [
                'data_type' => 'chatbot_conversations',
                'retention_period_days' => 30,
                'legal_basis' => 'Consent - customer service improvement',
                'deletion_method' => 'automatic',
                'is_active' => true,
            ],
            [
                'data_type' => 'financial_records',
                'retention_period_days' => 2555, // 7 years
                'legal_basis' => 'Legal obligation - tax compliance',
                'deletion_method' => 'manual_review',
                'is_active' => true,
            ],
        ];

        foreach ($policies as $policy) {
            DataRetentionPolicy::create($policy);
        }

        $this->command->info('Created ' . count($policies) . ' data retention policies.');
    }
}
SEEDER,

];

$seederDir = __DIR__ . '/database/seeders';
if (!is_dir($seederDir)) {
    mkdir($seederDir, 0755, true);
}

foreach ($seeders as $name => $content) {
    $file = "$seederDir/{$name}.php";
    if (file_exists($file) && filesize($file) > 300) {
        echo "SKIP (has content): {$name}.php\n";
        continue;
    }
    file_put_contents($file, $content);
    echo "WROTE: {$name}.php (" . strlen($content) . " bytes)\n";
}

echo "\nDone.\n";
