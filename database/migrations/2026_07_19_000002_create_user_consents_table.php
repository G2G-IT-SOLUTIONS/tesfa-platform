<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('consent_type', [
                'terms_of_service', 'privacy_policy', 'data_processing',
                'marketing_emails', 'marketing_sms', 'marketing_push',
                'cookie_tracking', 'analytics', 'third_party_sharing',
                'location_tracking', 'biometric_data', 'credit_check'
            ]);
            $table->boolean('granted');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('consent_version', 20);
            $table->text('withdrawal_reason')->nullable();
            $table->enum('withdrawn_by', ['user', 'system', 'admin'])->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'consent_type'], 'uk_consent_user_type');
            $table->index('consent_type');
            $table->index('granted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};