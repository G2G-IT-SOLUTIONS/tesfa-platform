<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_health_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_type', 100);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('description');
            $table->json('affected_metrics');
            
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            
            $table->text('root_cause')->nullable();
            $table->text('resolution')->nullable();
            $table->text('lessons_learned')->nullable();
            
            $table->timestamps();
            
            $table->index('incident_type');
            $table->index('severity');
            $table->index(['start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_health_incidents');
    }
};