<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->unique()->after('id');
            $table->enum('id_type', ['kebele_id', 'passport', 'drivers_license', 'refugee_id'])->nullable()->after('email');
            $table->string('id_number', 100)->nullable()->after('id_type');
            $table->string('id_document_path', 500)->nullable()->after('id_number');
            $table->boolean('id_verified')->default(false)->after('id_document_path');
            $table->timestamp('id_verified_at')->nullable()->after('id_verified');
            $table->unsignedBigInteger('id_verified_by')->nullable()->after('id_verified_at');
            
            $table->enum('role', [
                'buyer', 'seller', 'agent_certified', 'agent_premier', 
                'agent_master', 'concierge', 'admin', 'super_admin'
            ])->default('buyer')->after('id_verified_by');
            
            $table->string('country', 100)->default('Ethiopia')->after('role');
            $table->string('city', 100)->default('Addis Ababa')->after('country');
            $table->string('neighborhood', 100)->nullable()->after('city');
            $table->point('gps_location')->nullable()->after('neighborhood');
            $table->string('diaspora_location', 100)->nullable()->after('gps_location');
            
            $table->enum('preferred_currency', ['ETB', 'USD', 'EUR', 'GBP'])->default('ETB')->after('diaspora_location');
            $table->string('timezone', 50)->default('Africa/Addis_Ababa')->after('preferred_currency');
            $table->string('preferred_locale', 5)->default('en')->after('timezone');
            
            $table->boolean('mfa_enabled')->default(false)->after('preferred_locale');
            $table->string('mfa_secret', 255)->nullable()->after('mfa_enabled');
            $table->timestamp('last_login_at')->nullable()->after('mfa_secret');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->boolean('is_active')->default(true)->after('locked_until');
            $table->softDeletes()->after('updated_at');
            
            $table->foreign('id_verified_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('phone');
            $table->index('role');
            $table->index('neighborhood');
            $table->index('diaspora_location');
            
            // Spatial index for GPS
            //$table->spatialIndex('gps_location');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_verified_by']);
            $table->dropColumn([
                'phone', 'id_type', 'id_number', 'id_document_path',
                'id_verified', 'id_verified_at', 'id_verified_by', 'role',
                'country', 'city', 'neighborhood', 'gps_location',
                'diaspora_location', 'preferred_currency', 'timezone',
                'preferred_locale', 'mfa_enabled', 'mfa_secret',
                'last_login_at', 'failed_login_attempts', 'locked_until',
                'is_active', 'deleted_at'
            ]);
        });
    }
};