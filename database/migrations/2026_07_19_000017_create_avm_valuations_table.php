<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avm_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('estimated_value', 15, 2);
            $table->decimal('confidence_score', 5, 4);
            $table->decimal('value_range_low', 15, 2);
            $table->decimal('value_range_high', 15, 2);
            
            $table->string('model_version', 20);
            $table->json('features_used');
            $table->json('feature_importance');
            $table->json('comparable_properties');
            $table->json('market_conditions');
            
            $table->enum('validation_status', ['pending', 'approved', 'rejected', 'validated'])->default('pending');
            $table->decimal('actual_sale_price', 15, 2)->nullable();
            $table->decimal('accuracy_pct', 5, 2)->nullable();
            
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index('property_id');
            $table->index('confidence_score');
            $table->index('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avm_valuations');
    }
};