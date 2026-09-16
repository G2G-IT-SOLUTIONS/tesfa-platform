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