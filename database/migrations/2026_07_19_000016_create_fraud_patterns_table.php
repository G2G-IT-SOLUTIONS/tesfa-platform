<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('pattern_name', 255);
            $table->enum('pattern_type', ['temporal', 'geographic', 'behavioral', 'network']);
            $table->text('description');
            $table->json('detection_rules');
            $table->unsignedInteger('hit_count')->default(0);
            $table->unsignedInteger('false_positive_count')->default(0);
            $table->decimal('accuracy_rate', 5, 2)->storedAs(
                'CASE WHEN hit_count > 0 THEN (hit_count - false_positive_count) / hit_count * 100 ELSE 0 END'
            );
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('pattern_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_patterns');
    }
};