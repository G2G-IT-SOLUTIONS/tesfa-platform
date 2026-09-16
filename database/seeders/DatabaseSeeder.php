<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            PropertySeeder::class,
            MarketMetricSeeder::class,
            DemandEventSeeder::class,
            ChatbotKnowledgeSeeder::class,
            DataRetentionPolicySeeder::class,
        ]);
    }
}