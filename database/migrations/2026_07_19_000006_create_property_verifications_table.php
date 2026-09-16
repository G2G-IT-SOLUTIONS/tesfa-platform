<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->string('job_number', 50)->unique();
            
            $table->enum('status', [
                'scheduled', 'assigned', 'accepted', 'in_transit', 
                'at_property', 'in_progress', 'completed', 'rejected', 
                'cancelled', 'expired'
            ])->default('scheduled');
            
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Agent report
            $table->json('agent_report')->nullable();
            $table->json('agent_photos')->nullable();
            $table->string('agent_video_path', 500)->nullable();
            $table->text('agent_notes')->nullable();
            
            // GPS verification
            $table->point('agent_gps_at_start')->nullable();
            $table->point('agent_gps_at_property')->nullable();
            $table->decimal('gps_distance_meters', 10, 2)->nullable();
            
            // Documents
            $table->json('documents_submitted')->nullable();
            $table->boolean('documents_verified')->default(false);
            $table->text('document_verification_notes')->nullable();
            
            // Condition assessment
            $table->unsignedTinyInteger('condition_rating')->nullable();
            $table->text('condition_notes')->nullable();
            
            // Dispute checks
            $table->boolean('dispute_check_passed')->default(false);
            $table->text('dispute_check_notes')->nullable();
            
            // Blockchain anchoring (Phase 3)
            $table->string('blockchain_tx_hash', 255)->nullable();
            $table->timestamp('blockchain_anchored_at')->nullable();
            
            // Review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            
            // Earnings
            $table->decimal('base_fee', 10, 2)->default(300);
            $table->decimal('distance_bonus', 10, 2)->default(0);
            $table->decimal('urgency_bonus', 10, 2)->default(0);
            $table->decimal('quality_bonus', 10, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_verifications');
    }
};