<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_term_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 50)->unique();
            
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('host_id')->constrained('users')->restrictOnDelete();
            
            // Dates
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('nights');
            $table->unsignedInteger('guests')->default(1);
            
            // Pricing
            $table->decimal('nightly_rate', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('security_deposit', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('host_payout', 12, 2)->nullable();
            $table->decimal('platform_fee', 12, 2)->nullable();
            $table->enum('currency', ['ETB', 'USD'])->default('ETB');
            
            // Status
            $table->enum('status', [
                'pending', 'confirmed', 'checked_in', 'checked_out',
                'cancelled', 'completed', 'disputed', 'no_show'
            ])->default('pending');
            
            // Payment
            $table->enum('payment_status', [
                'pending', 'paid', 'refunded', 'partially_refunded', 'failed'
            ])->default('pending');
            $table->string('payment_reference', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['guest', 'host', 'system'])->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            
            // Reviews
            $table->timestamp('guest_reviewed_at')->nullable();
            $table->timestamp('host_reviewed_at')->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index(['check_in', 'check_out']);
            $table->index('property_id');
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_term_bookings');
    }
};