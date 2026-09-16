<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // Score (300-850)
            $table->unsignedSmallInteger('score')->default(450);
            $table->enum('score_tier', ['poor', 'fair', 'good', 'very_good', 'excellent'])->default('poor');
            
            // Component scores (weighted)
            $table->decimal('payment_history_score', 5, 2)->default(0); // 35% weight
            $table->decimal('amounts_owed_score', 5, 2)->default(0); // 30% weight
            $table->decimal('credit_history_length_score', 5, 2)->default(0); // 15% weight
            $table->decimal('credit_mix_score', 5, 2)->default(0); // 10% weight
            $table->decimal('new_credit_score', 5, 2)->default(0); // 10% weight
            
            // Payment history details
            $table->unsignedInteger('rent_to_own_payments_on_time')->default(0);
            $table->unsignedInteger('rent_to_own_payments_total')->default(0);
            $table->unsignedInteger('rent_to_own_payments_late')->default(0);
            $table->unsignedInteger('rent_to_own_payments_missed')->default(0);
            $table->decimal('rent_to_own_default_amount', 15, 2)->default(0);
            
            // Platform engagement
            $table->decimal('telebirr_transaction_volume', 15, 2)->default(0);
            $table->unsignedInteger('telebirr_transaction_count')->default(0);
            $table->unsignedInteger('platform_engagement_months')->default(0);
            $table->unsignedInteger('platform_verified_transactions')->default(0);
            
            // Social proof
            $table->unsignedInteger('community_references_count')->default(0);
            $table->unsignedInteger('community_references_positive')->default(0);
            
            // Employment
            $table->boolean('employment_verified')->default(false);
            $table->unsignedInteger('employment_duration_months')->default(0);
            $table->string('employer_name', 255)->nullable();
            $table->decimal('monthly_income_verified', 12, 2)->nullable();
            
            // Score tracking
            $table->smallInteger('score_change_last_30_days')->default(0);
            $table->smallInteger('score_change_last_90_days')->default(0);
            $table->json('score_history')->nullable();
            
            // Status
            $table->enum('status', ['active', 'frozen', 'under_review', 'disputed'])->default('active');
            $table->text('frozen_reason')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->foreignId('frozen_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Model tracking
            $table->string('model_version', 20);
            $table->timestamp('computed_at');
            $table->timestamp('next_computation_at');
            $table->enum('computation_trigger', ['scheduled', 'payment_event', 'manual', 'dispute'])->default('scheduled');
            
            $table->timestamps();
            
            $table->index('score');
            $table->index('score_tier');
            $table->index('status');
            $table->index('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_scores');
    }
};