<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('tier', ['certified', 'premier', 'master'])->default('certified');
            $table->string('license_number', 100)->unique()->nullable();
            $table->date('license_expires_at')->nullable();
            $table->string('coverage_area', 255)->nullable();
            $table->json('specializations')->nullable(); // ['residential', 'commercial']
            
            // Performance metrics
            $table->unsignedInteger('total_verifications')->default(0);
            $table->unsignedInteger('successful_verifications')->default(0);
            $table->unsignedInteger('disputed_verifications')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_ratings')->default(0);
            
            // Earnings
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('pending_earnings', 15, 2)->default(0);
            $table->decimal('base_fee', 10, 2)->default(300);
            
            // Status
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_job_at')->nullable();
            $table->string('current_gps', 100)->nullable();
            
            $table->timestamps();
            
            $table->index('tier');
            $table->index('is_available');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_profiles');
    }
};