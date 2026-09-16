<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_to_own_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            
            // Financial terms
            $table->decimal('property_price', 15, 2);
            $table->decimal('down_payment', 15, 2);
            $table->decimal('down_payment_pct', 5, 2);
            $table->decimal('monthly_payment', 12, 2);
            $table->unsignedInteger('duration_months');
            $table->decimal('total_cost', 15, 2);
            $table->decimal('remaining_balance', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->unsignedInteger('payments_made')->default(0);
            $table->unsignedInteger('payments_remaining');
            
            // Status
            $table->enum('status', [
                'draft', 'pending_approval', 'active', 'completed', 
                'defaulted', 'cancelled', 'expired'
            ])->default('draft');
            
            // Dates
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_payment_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('defaulted_at')->nullable();
            
            // Credit assessment
            $table->unsignedSmallInteger('credit_score_at_approval')->nullable();
            $table->json('credit_assessment')->nullable();
            
            // Default tracking
            $table->unsignedInteger('missed_payments')->default(0);
            $table->unsignedInteger('late_payments')->default(0);
            $table->decimal('default_amount', 15, 2)->nullable();
            
            // Documents
            $table->string('contract_pdf_path', 500)->nullable();
            $table->json('signed_documents')->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('next_payment_date');
            $table->index('buyer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_to_own_contracts');
    }
};