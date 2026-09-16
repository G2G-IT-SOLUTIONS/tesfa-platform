<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('verification_id')->nullable()->constrained('property_verifications')->nullOnDelete();
            
            $table->enum('type', [
                'lease_certificate', 'building_permit', 'ownership_document',
                'id_document', 'tax_record', 'utility_bill', 'bank_statement',
                'employment_letter', 'reference_letter', 'contract', 'other'
            ]);
            
            $table->string('original_filename', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('sha256_hash', 64)->unique();
            $table->string('ipfs_hash', 255)->nullable();
            
            $table->enum('status', [
                'pending', 'verified', 'rejected', 'expired', 'revoked'
            ])->default('pending');
            
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority', 255)->nullable();
            $table->string('document_number', 100)->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('status');
            $table->index('sha256_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};