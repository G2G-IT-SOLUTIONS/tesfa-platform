<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_score_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_score_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->unsignedSmallInteger('old_score');
            $table->unsignedSmallInteger('new_score');
            $table->smallInteger('score_change');
            
            $table->enum('change_reason', [
                'payment_on_time', 'payment_late', 'payment_missed',
                'kyc_completed', 'employment_verified', 'telebirr_volume_increase',
                'reference_added', 'fraud_detected', 'manual_review', 'model_update'
            ]);
            
            $table->json('change_details')->nullable();
            $table->enum('triggered_by', ['system', 'user', 'admin', 'bank']);
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_score_logs');
    }
};