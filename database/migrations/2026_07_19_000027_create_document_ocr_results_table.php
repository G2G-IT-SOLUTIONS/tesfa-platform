<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ocr_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            
            $table->enum('ocr_engine', ['tesseract', 'aws_textract', 'azure_form_recognizer']);
            $table->decimal('ocr_confidence', 5, 4);
            $table->unsignedInteger('processing_time_ms')->nullable();
            
            $table->longText('extracted_text')->nullable();
            $table->json('extracted_fields')->nullable();
            
            $table->enum('validation_status', ['pending', 'validated', 'failed', 'manual_review'])->default('pending');
            $table->json('validation_errors')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            
            $table->json('auto_filled_fields')->nullable();
            $table->json('manual_corrections')->nullable();
            $table->unsignedInteger('time_saved_minutes')->nullable();
            
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ocr_results');
    }
};