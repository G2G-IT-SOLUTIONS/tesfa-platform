<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->id();
            
            $table->string('neighborhood', 100);
            $table->string('sub_city', 100)->nullable();
            $table->enum('property_type', ['apartment', 'villa', 'townhouse', 'land', 'commercial', 'mixed_use']);
            
            $table->date('forecast_date');
            $table->date('prediction_for_date');
            $table->unsignedInteger('days_ahead');
            
            $table->decimal('demand_index', 5, 2);
            $table->unsignedInteger('predicted_searches')->nullable();
            $table->unsignedInteger('predicted_inquiries')->nullable();
            $table->unsignedInteger('predicted_transactions')->nullable();
            
            $table->decimal('demand_index_low', 5, 2)->nullable();
            $table->decimal('demand_index_high', 5, 2)->nullable();
            
            $table->json('features_used');
            
            $table->string('model_version', 20);
            $table->enum('model_type', ['prophet', 'arima', 'lstm', 'ensemble']);
            $table->date('training_data_start');
            $table->date('training_data_end');
            $table->unsignedInteger('training_samples');
            
            $table->decimal('actual_value', 10, 2)->nullable();
            $table->decimal('accuracy_pct', 5, 2)->nullable();
            $table->boolean('is_validated')->default(false);
            
            $table->enum('status', ['active', 'expired', 'validated', 'invalidated'])->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('computed_at')->useCurrent();
            
            $table->timestamps();
            
            $table->unique(
                ['neighborhood', 'property_type', 'prediction_for_date', 'model_version'],
                'uk_demand_unique'
            );
            $table->index('neighborhood');
            $table->index('property_type');
            $table->index('forecast_date');
            $table->index('prediction_for_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_forecasts');
    }
};