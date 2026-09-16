<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            
            $table->enum('alert_type', [
                'duplicate_listing', 'price_anomaly', 'fake_document',
                'agent_collusion', 'suspicious_payment', 'identity_theft',
                'image_theft', 'velocity_abuse', 'account_takeover',
                'geolocation_mismatch'
            ]);
            
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('target_type', ['user', 'property', 'transaction', 'agent', 'document']);
            $table->unsignedBigInteger('target_id');
            $table->string('target_type_id', 50);
            
            // Detection metadata
            $table->enum('detection_method', [
                'rule_based', 'ml_model', 'manual_review', 'user_report', 'system_trigger'
            ]);
            $table->string('ml_model_version', 20)->nullable();
            $table->decimal('confidence_score', 5, 4)->default(0);
            
            // Evidence
            $table->json('evidence');
            $table->json('evidence_screenshots')->nullable();
            
            // Workflow
            $table->enum('status', [
                'new', 'under_review', 'confirmed_fraud', 'false_positive',
                'escalated', 'resolved'
            ])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Actions
            $table->json('actions_taken')->nullable();
            
            // Notifications
            $table->boolean('user_notified')->default(false);
            $table->boolean('admin_notified')->default(false);
            $table->boolean('law_enforcement_notified')->default(false);
            
            // Feedback loop
            $table->enum('feedback_loop_status', ['pending', 'incorporated', 'rejected'])->default('pending');
            $table->boolean('model_retrain_triggered')->default(false);
            
            $table->timestamps();
            
            $table->index('alert_type');
            $table->index('severity');
            $table->index('status');
            $table->index(['target_type', 'target_id'], 'idx_fraud_target');
            $table->index('confidence_score');
            $table->index('created_at');
            $table->index(['assigned_to', 'status'], 'idx_fraud_assigned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_alerts');
    }
};