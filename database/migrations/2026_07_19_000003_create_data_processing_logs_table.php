<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('processing_activity', [
                'registration', 'property_listing', 'verification', 'escrow',
                'payment', 'credit_scoring', 'marketing', 'analytics',
                'fraud_detection', 'data_export', 'data_deletion', 'data_correction'
            ]);
            $table->enum('legal_basis', [
                'consent', 'contract', 'legal_obligation', 
                'vital_interests', 'public_task', 'legitimate_interests'
            ]);
            $table->json('data_categories');
            $table->unsignedInteger('retention_period_days');
            $table->json('third_parties_shared')->nullable();
            $table->string('processed_in', 100)->default('Ethiopia');
            $table->boolean('data_transfer_outside_eea')->default(false);
            $table->text('transfer_safeguards')->nullable();
            $table->timestamps();

            $table->index('processing_activity');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_processing_logs');
    }
};