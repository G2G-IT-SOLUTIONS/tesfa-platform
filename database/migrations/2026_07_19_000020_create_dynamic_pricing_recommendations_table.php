<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_pricing_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('current_rate', 10, 2);
            $table->decimal('recommended_rate', 10, 2);
            $table->decimal('rate_change_pct', 5, 2)->storedAs(
                '((recommended_rate - current_rate) / NULLIF(current_rate, 0)) * 100'
            );
            $table->decimal('potential_revenue_increase', 12, 2)->nullable();
            
            // Pricing components
            $table->decimal('base_rate', 10, 2);
            $table->decimal('demand_multiplier', 3, 2)->default(1.00);
            $table->decimal('event_multiplier', 3, 2)->default(1.00);
            $table->decimal('competitor_adjustment', 3, 2)->default(1.00);
            $table->decimal('day_of_week_multiplier', 3, 2)->default(1.00);
            $table->decimal('seasonal_multiplier', 3, 2)->default(1.00);
            
            // Context
            $table->date('pricing_date');
            $table->unsignedInteger('days_until_event')->nullable();
            $table->string('event_name', 255)->nullable();
            $table->decimal('competitor_rates_avg', 10, 2)->nullable();
            $table->unsignedInteger('competitor_rates_count')->nullable();
            
            // Host interaction
            $table->enum('host_action', ['pending', 'accepted', 'rejected', 'auto_applied'])->default('pending');
            $table->timestamp('host_action_at')->nullable();
            $table->text('host_action_reason')->nullable();
            
            // Performance
            $table->unsignedInteger('actual_bookings')->nullable();
            $table->decimal('actual_revenue', 12, 2)->nullable();
            $table->decimal('vs_manual_pricing_revenue', 12, 2)->nullable();
            
            $table->string('model_version', 20);
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamp('expires_at');
            
            $table->timestamps();
            
            $table->index('property_id');
            $table->index('pricing_date');
            $table->index('host_action');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_pricing_recommendations');
    }
};