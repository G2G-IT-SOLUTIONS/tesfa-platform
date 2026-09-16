<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_to_own_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('rent_to_own_contracts')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            
            $table->unsignedInteger('payment_number');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->decimal('late_fee', 12, 2)->default(0);
            
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            
            $table->enum('status', [
                'pending', 'paid', 'late', 'missed', 'partial', 'waived'
            ])->default('pending');
            
            $table->enum('payment_method', ['telebirr', 'bank_transfer', 'cash'])->nullable();
            $table->string('transaction_reference', 255)->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->unique(['contract_id', 'payment_number']);
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_to_own_payments');
    }
};