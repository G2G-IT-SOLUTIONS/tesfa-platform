<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->enum('type', ['apartment', 'villa', 'townhouse', 'land', 'commercial', 'mixed_use']);
            $table->enum('purpose', ['sale', 'rent', 'short_term', 'rent_to_own']);
            
            // Pricing
            $table->decimal('price', 15, 2);
            $table->enum('price_currency', ['ETB', 'USD', 'EUR', 'GBP'])->default('ETB');
            $table->boolean('price_negotiable')->default(true);
            $table->decimal('price_per_sqm', 10, 2)->storedAs('price / NULLIF(area_sqm, 0)');
            
            // Rent-to-own options
            $table->boolean('rent_to_own_enabled')->default(false);
            $table->decimal('rent_to_own_down_payment_pct', 5, 2)->nullable();
            $table->decimal('rent_to_own_monthly_payment', 12, 2)->nullable();
            $table->unsignedInteger('rent_to_own_duration_months')->nullable();
            $table->decimal('rent_to_own_total_cost', 15, 2)->nullable();
            
            // Short-term options
            $table->boolean('short_term_enabled')->default(false);
            $table->decimal('nightly_rate', 10, 2)->nullable();
            $table->enum('nightly_rate_currency', ['ETB', 'USD'])->default('ETB');
            $table->unsignedInteger('min_nights')->default(1);
            $table->unsignedInteger('max_nights')->default(30);
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('security_deposit', 10, 2)->default(0);
            
            // Property details
            $table->unsignedInteger('bedrooms')->nullable();
            $table->unsignedInteger('bathrooms')->nullable();
            $table->unsignedInteger('total_rooms')->nullable();
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->integer('floor_number')->nullable();
            $table->integer('total_floors')->nullable();
            $table->unsignedInteger('year_built')->nullable();
            $table->enum('condition', ['new', 'excellent', 'good', 'fair', 'needs_renovation'])->nullable();
            
            // Location
            $table->text('address');
            $table->string('neighborhood', 100);
            $table->string('sub_city', 100)->nullable();
            $table->string('city', 100)->default('Addis Ababa');
            $table->string('region', 100)->default('Addis Ababa');
            $table->string('country', 100)->default('Ethiopia');
            $table->point('location');
            
            // Features
            $table->json('features')->nullable();
            $table->json('amenities')->nullable();
            $table->json('nearby')->nullable();
            $table->json('photos')->nullable();
            $table->json('videos')->nullable();
            $table->string('virtual_tour_url', 500)->nullable();
            $table->string('floor_plan_url', 500)->nullable();
            
            // Verification
            $table->enum('verification_status', [
                'unverified', 'pending', 'in_progress', 'verified', 'rejected', 'expired'
            ])->default('unverified');
            $table->string('passport_id', 100)->unique()->nullable();
            $table->timestamp('passport_issued_at')->nullable();
            $table->timestamp('passport_expires_at')->nullable();
            
            // Document hashes for verification
            $table->string('lease_certificate_hash', 255)->nullable();
            $table->string('building_permit_hash', 255)->nullable();
            $table->string('ownership_document_hash', 255)->nullable();
            
            // Status
            $table->enum('status', [
                'draft', 'pending_review', 'active', 'paused', 'sold', 'rented', 'withdrawn'
            ])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->enum('featured_tier', ['basic', 'premium', 'enterprise'])->nullable();
            
            // Metrics
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('inquiry_count')->default(0);
            $table->unsignedInteger('save_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            
            // SEO
            $table->string('slug', 500)->unique();
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->softDeletes();
            
            // Indexes
            $table->index('price');
            $table->index('neighborhood');
            $table->index('type');
            $table->index('purpose');
            $table->index('verification_status');
            $table->index('status');
            $table->index(['is_featured', 'featured_until']);
            $table->fullText(['title', 'description', 'address'], 'idx_properties_search');
            //$table->spatialIndex('location', 'idx_properties_location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};