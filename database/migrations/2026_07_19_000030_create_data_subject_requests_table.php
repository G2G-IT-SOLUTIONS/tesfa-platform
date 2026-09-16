<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->enum('request_type', [
                'access', 'rectification', 'erasure', 'restriction',
                'portability', 'objection', 'automated_decision'
            ]);
            $table->text('request_description')->nullable();
            
            $table->enum('status', [
                'received', 'under_review', 'information_gathering',
                'verification_required', 'fulfilled', 'partially_fulfilled',
                'rejected', 'extended'
            ])->default('received');
            
            $table->timestamp('received_at');
            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('extension_reason')->nullable();
            
            $table->text('response_summary')->nullable();
            $table->decimal('response_data_size_mb', 8, 2)->nullable();
            $table->string('response_file_path', 500)->nullable();
            
            $table->text('rejection_reason')->nullable();
            $table->text('rejection_legal_basis')->nullable();
            
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('due_at');
        });

        Schema::create('data_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('data_type', 100);
            $table->unsignedInteger('retention_period_days');
            $table->text('legal_basis');
            $table->enum('deletion_method', ['automatic', 'manual_review', 'anonymization']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('data_retention_policies');
    }
};