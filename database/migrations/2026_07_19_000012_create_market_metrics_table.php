<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('neighborhood', 100);
            $table->string('sub_city', 100)->nullable();
            $table->enum('property_type', ['apartment', 'villa', 'townhouse', 'land', 'commercial', 'mixed_use', 'all'])->default('all');
            
            $table->date('metric_date');
            
            // Pricing metrics
            $table->decimal('avg_price_per_sqm', 12, 2)->nullable();
            $table->decimal('median_price_per_sqm', 12, 2)->nullable();
            $table->decimal('min_price_per_sqm', 12, 2)->nullable();
            $table->decimal('max_price_per_sqm', 12, 2)->nullable();
            $table->decimal('price_change_30d', 5, 2)->nullable();
            $table->decimal('price_change_90d', 5, 2)->nullable();
            
            // Inventory metrics
            $table->unsignedInteger('total_listings')->default(0);
            $table->unsignedInteger('active_listings')->default(0);
            $table->unsignedInteger('new_listings_7d')->default(0);
            $table->unsignedInteger('sold_listings_7d')->default(0);
            
            // Demand metrics
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_inquiries')->default(0);
            $table->unsignedInteger('total_saves')->default(0);
            $table->decimal('avg_days_on_market', 8, 2)->nullable();
            $table->decimal('demand_score', 5, 2)->nullable(); // 0-100
            
            // Rent metrics
            $table->decimal('avg_rental_yield', 5, 2)->nullable();
            $table->decimal('avg_monthly_rent', 12, 2)->nullable();
            
            $table->timestamps();
            
            $table->unique(['neighborhood', 'property_type', 'metric_date'], 'uk_market_metrics');
            $table->index('neighborhood');
            $table->index('metric_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_metrics');
    }
};