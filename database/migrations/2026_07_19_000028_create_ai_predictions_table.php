<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id();
            
            $table->enum('prediction_type', [
                'price_prediction', 'demand_forecast', 'fraud_probability',
                'churn_risk', 'dynamic_price'
            ]);
            $table->enum('target_type', ['property', 'neighborhood', 'user', 'market']);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name', 255)->nullable();
            
            $table->decimal('predicted_value', 15, 2);
            $table->decimal('predicted_value_min', 15, 2)->nullable();
            $table->decimal('predicted_value_max', 15, 2)->nullable();
            $table->decimal('confidence', 5, 4);
            
            $table->json('features_used');
            $table->json('feature_importance')->nullable();
            
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            
            $table->string('model_version', 20);
            $table->enum('model_type', ['linear_regression', 'random_forest', 'xgboost', 'lstm', 'prophet']);
            $table->date('training_data_start');
            $table->date('training_data_end');
            $table->unsignedInteger('training_samples');
            
            $table->date('prediction_for_date')->nullable();
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'validated', 'invalidated'])->default('active');
            
            $table->timestamps();
            
            $table->index('prediction_type');
            $table->index(['target_type', 'target_id'], 'idx_ai_target');
            $table->index('prediction_for_date');
            $table->index('model_version');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_predictions');
    }
};