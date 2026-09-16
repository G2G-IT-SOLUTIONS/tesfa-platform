<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 255);
            $table->enum('event_type', ['holiday', 'festival', 'summit', 'sports', 'religious', 'economic', 'weather']);
            $table->date('start_date');
            $table->date('end_date');
            $table->json('affected_neighborhoods');
            $table->decimal('expected_demand_multiplier', 3, 2);
            $table->decimal('actual_multiplier', 3, 2)->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['start_date', 'end_date']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_events');
    }
};