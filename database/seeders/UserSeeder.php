<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AgentProfile;
use App\Models\CreditScore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'Ted Ross',
            'email' => 'admin@tesfa.et',
            'phone' => '+251911000001',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'id_verified' => true,
            'id_verified_at' => now(),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super_admin');

        // Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin2@tesfa.et',
            'phone' => '+251911000002',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'id_verified' => true,
            'id_verified_at' => now(),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Agent
        $agent = User::create([
            'name' => 'Abebe Kebede',
            'email' => 'agent@tesfa.et',
            'phone' => '+251911000003',
            'password' => Hash::make('password'),
            'role' => 'agent_master',
            'id_type' => 'kebele_id',
            'id_number' => 'AK123456',
            'id_verified' => true,
            'id_verified_at' => now(),
            'neighborhood' => 'Bole',
            'city' => 'Addis Ababa',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $agent->assignRole('agent_master');

        AgentProfile::create([
            'user_id' => $agent->id,
            'tier' => 'master',
            'license_number' => 'AGT-2026-0001',
            'license_expires_at' => now()->addYear(),
            'coverage_area' => 'Bole, CMC, Megenagna',
            'specializations' => ['residential', 'commercial'],
            'total_verifications' => 150,
            'successful_verifications' => 148,
            'average_rating' => 4.85,
            'total_ratings' => 120,
            'total_earnings' => 45000,
            'is_available' => true,
            'is_active' => true,
        ]);

        // Seller
        $seller = User::create([
            'name' => 'Sara Tesfaye',
            'email' => 'seller@tesfa.et',
            'phone' => '+251911000004',
            'password' => Hash::make('password'),
            'role' => 'seller',
            'id_type' => 'passport',
            'id_number' => 'ET1234567',
            'id_verified' => true,
            'id_verified_at' => now(),
            'neighborhood' => 'CMC',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $seller->assignRole('seller');

        // Buyer
        $buyer = User::create([
            'name' => 'John Doe',
            'email' => 'buyer@tesfa.et',
            'phone' => '+251911000005',
            'password' => Hash::make('password'),
            'role' => 'buyer',
            'diaspora_location' => 'London, UK',
            'preferred_currency' => 'GBP',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $buyer->assignRole('buyer');

        CreditScore::create([
            'user_id' => $buyer->id,
            'score' => 720,
            'score_tier' => 'good',
            'payment_history_score' => 85,
            'amounts_owed_score' => 70,
            'credit_history_length_score' => 60,
            'credit_mix_score' => 50,
            'new_credit_score' => 80,
            'model_version' => 'credit_v1.0',
            'computed_at' => now(),
            'next_computation_at' => now()->addWeek(),
        ]);

        $this->command->info('Users seeded successfully.');
        $this->command->info('Login credentials:');
        $this->command->info('Super Admin: admin@tesfa.et / password');
        $this->command->info('Agent: agent@tesfa.et / password');
        $this->command->info('Seller: seller@tesfa.et / password');
        $this->command->info('Buyer: buyer@tesfa.et / password');
    }
}