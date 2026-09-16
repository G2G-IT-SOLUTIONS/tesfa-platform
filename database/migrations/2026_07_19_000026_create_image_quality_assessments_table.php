<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_quality_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 500);
            
            $table->decimal('overall_score', 5, 2);
            $table->decimal('resolution_score', 5, 2);
            $table->decimal('blur_score', 5, 2);
            $table->decimal('lighting_score', 5, 2);
            $table->decimal('composition_score', 5, 2);
            
            $table->json('rekognition_labels')->nullable();
            $table->json('detected_rooms')->nullable();
            $table->json('detected_objects')->nullable();
            $table->boolean('has_faces')->nullable();
            
            $table->string('perceptual_hash', 64)->nullable();
            $table->unsignedBigInteger('duplicate_of')->nullable();
            
            $table->enum('status', ['approved', 'rejected', 'needs_improvement']);
            $table->json('rejection_reasons')->nullable();
            $table->json('improvement_suggestions')->nullable();
            
            $table->enum('seller_action', ['pending', 'reuploaded', 'appealed', 'accepted'])->default('pending');
            $table->timestamp('seller_action_at')->nullable();
            
            $table->timestamps();
            
            $table->index('property_id');
            $table->index('status');
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_quality_assessments');
    }
};