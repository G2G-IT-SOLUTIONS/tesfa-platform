<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Property permissions
            'properties.view',
            'properties.create',
            'properties.edit',
            'properties.delete',
            'properties.verify',
            'properties.feature',

            // Verification permissions
            'verifications.view',
            'verifications.assign',
            'verifications.complete',
            'verifications.review',

            // Escrow permissions
            'escrows.view',
            'escrows.create',
            'escrows.manage',
            'escrows.resolve-dispute',

            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.verify-id',
            'users.ban',

            // Financial
            'payments.view',
            'payments.process',
            'refunds.process',

            // AI & Analytics
            'ai.view-analytics',
            'ai.manage-models',
            'ai.view-fraud',
            'ai.resolve-fraud',
            'ai.view-credit',
            'ai.manage-credit',

            // System
            'system.view-health',
            'system.manage-settings',
            'system.view-logs',
            'system.backup',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $buyer = Role::create(['name' => 'buyer']);
        $buyer->givePermissionTo([
            'properties.view',
            'escrows.view',
            'escrows.create',
        ]);

        $seller = Role::create(['name' => 'seller']);
        $seller->givePermissionTo([
            'properties.view',
            'properties.create',
            'properties.edit',
            'escrows.view',
        ]);

        $agentCertified = Role::create(['name' => 'agent_certified']);
        $agentCertified->givePermissionTo([
            'properties.view',
            'verifications.view',
            'verifications.complete',
        ]);

        $agentPremier = Role::create(['name' => 'agent_premier']);
        $agentPremier->givePermissionTo([
            'properties.view',
            'properties.create',
            'properties.edit',
            'properties.verify',
            'verifications.view',
            'verifications.assign',
            'verifications.complete',
            'verifications.review',
        ]);

        $agentMaster = Role::create(['name' => 'agent_master']);
        $agentMaster->givePermissionTo([
            'properties.view',
            'properties.create',
            'properties.edit',
            'properties.verify',
            'properties.feature',
            'verifications.view',
            'verifications.assign',
            'verifications.complete',
            'verifications.review',
        ]);

        $concierge = Role::create(['name' => 'concierge']);
        $concierge->givePermissionTo([
            'properties.view',
            'escrows.view',
            'escrows.manage',
        ]);

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $this->command->info('Roles and permissions created successfully.');
    }
}