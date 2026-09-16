<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: Partitioning requires raw SQL in a separate statement
        Schema::create('user_behavior_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 255);
            
            $table->enum('event_type', [
                'page_view', 'search', 'click', 'scroll', 'form_start', 'form_submit',
                'purchase_intent', 'save_property', 'share_property', 'contact_agent',
                'video_play', 'virtual_tour', 'map_interaction', 'filter_apply',
                'login', 'register', 'logout', 'app_install'
            ]);
            $table->string('event_name', 255);
            
            $table->string('page_url', 500);
            $table->string('page_path', 255);
            $table->string('referrer', 500)->nullable();
            
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('property_type', 50)->nullable();
            $table->decimal('property_price', 15, 2)->nullable();
            $table->string('property_neighborhood', 100)->nullable();
            
            $table->string('search_query', 255)->nullable();
            $table->json('search_filters')->nullable();
            $table->unsignedInteger('search_results_count')->nullable();
            
            $table->string('element_id', 255)->nullable();
            $table->string('element_text', 255)->nullable();
            $table->string('element_type', 50)->nullable();
            
            $table->unsignedInteger('time_on_page')->nullable();
            $table->unsignedInteger('time_on_element')->nullable();
            $table->unsignedTinyInteger('scroll_depth')->nullable();
            
            $table->enum('device_type', ['mobile', 'tablet', 'desktop', 'unknown']);
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('screen_resolution', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 100)->default('Ethiopia');
            $table->string('city', 100)->nullable();
            
            $table->decimal('conversion_value', 15, 2)->nullable();
            $table->string('conversion_type', 50)->nullable();
            
            $table->decimal('engagement_score', 5, 2)->nullable();
            $table->decimal('intent_score', 5, 2)->nullable();
            $table->decimal('churn_risk_score', 5, 2)->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('user_id');
            $table->index('session_id');
            $table->index('event_type');
            $table->index('property_id');
            $table->index('created_at');
            $table->index('intent_score');
            $table->index(['user_id', 'event_type', 'created_at'], 'idx_behavior_composite');
        });

        // Add partitioning (MySQL 8.0)
        // DB::statement("
        //     ALTER TABLE user_behavior_logs
        //     PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
        //         PARTITION p202607 VALUES LESS THAN (202608),
        //         PARTITION p202608 VALUES LESS THAN (202609),
        //         PARTITION p202609 VALUES LESS THAN (202610),
        //         PARTITION p_future VALUES LESS THAN MAXVALUE
        //     )
        // ");

    }

    public function down(): void
    {
        Schema::dropIfExists('user_behavior_logs');
    }
};