<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->string('escrow_number', 50)->unique();
            
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('concierge_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Blockchain (Phase 3)
            $table->string('contract_address', 255)->nullable();
            $table->timestamp('contract_deployed_at')->nullable();
            
            // Pricing
            $table->decimal('property_price', 15, 2);
            $table->enum('currency', ['ETB', 'USD', 'USDT', 'USDC'])->default('ETB');
            $table->decimal('platform_fee_pct', 5, 2)->default(2.00);
            $table->decimal('agent_fee_pct', 5, 2)->default(0.50);
            $table->decimal('concierge_fee', 12, 2)->default(0);
            $table->decimal('platform_fee_amount', 12, 2)->nullable();
            $table->decimal('agent_fee_amount', 12, 2)->nullable();
            $table->decimal('seller_amount', 15, 2)->nullable();
            
            // Deposit
            $table->decimal('deposit_amount', 15, 2)->nullable();
            $table->enum('deposit_currency', ['ETB', 'USD', 'USDT', 'USDC'])->nullable();
            $table->enum('deposit_method', ['telebirr', 'bank_transfer', 'usdt', 'usdc', 'cash'])->nullable();
            $table->string('deposit_tx_hash', 255)->nullable();
            $table->timestamp('deposited_at')->nullable();
            $table->boolean('deposit_confirmed')->default(false);
            
            // Status workflow
            $table->enum('status', [
                'created', 'funded', 'documents_submitted', 'agent_verified',
                'buyer_confirmed', 'completed', 'disputed', 'refunded', 'cancelled'
            ])->default('created');
            
            // Documents
            $table->json('seller_documents')->nullable();
            $table->json('buyer_documents')->nullable();
            $table->json('document_hashes')->nullable();
            
            // Verification
            $table->foreignId('verification_id')->nullable()->constrained('property_verifications')->nullOnDelete();
            $table->timestamp('verification_completed_at')->nullable();
            
            // Dispute
            $table->unsignedInteger('dispute_window_hours')->default(336); // 14 days
            $table->timestamp('dispute_window_starts')->nullable();
            $table->timestamp('dispute_window_ends')->nullable();
            $table->timestamp('dispute_raised_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->timestamp('dispute_resolved_at')->nullable();
            $table->text('dispute_resolution')->nullable();
            $table->foreignId('dispute_resolved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Timestamps
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('documents_submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('escrow_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrows');
    }
};