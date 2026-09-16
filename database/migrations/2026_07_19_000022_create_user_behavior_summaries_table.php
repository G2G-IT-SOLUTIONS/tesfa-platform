<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_behavior_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date');
            
            $table->unsignedInteger('sessions_count')->default(0);
            $table->unsignedInteger('avg_session_duration')->default(0);
            
            $table->unsignedInteger('page_views_total')->default(0);
            $table->unsignedInteger('unique_pages_viewed')->default(0);
            
            $table->unsignedInteger('searches_count')->default(0);
            $table->unsignedInteger('avg_search_results')->default(0);
            
            $table->unsignedInteger('properties_viewed')->default(0);
            $table->unsignedInteger('properties_saved')->default(0);
            $table->unsignedInteger('properties_shared')->default(0);
            $table->unsignedInteger('properties_inquired')->default(0);
            
            $table->decimal('engagement_score', 5, 2)->default(0);
            $table->decimal('intent_score', 5, 2)->default(0);
            $table->decimal('churn_risk_score', 5, 2)->default(0);
            
            $table->enum('funnel_stage', [
                'visitor', 'browser', 'searcher', 'inquirer', 'buyer', 'churned'
            ])->default('visitor');
            
            $table->json('preferred_neighborhoods')->nullable();
            $table->json('preferred_property_types')->nullable();
            $table->decimal('price_range_min', 15, 2)->nullable();
            $table->decimal('price_range_max', 15, 2)->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'summary_date'], 'uk_summary_user_date');
            $table->index('engagement_score');
            $table->index('intent_score');
            $table->index('churn_risk_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_behavior_summaries');
    }
};