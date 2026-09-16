<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 255);
            
            $table->enum('message_role', ['user', 'assistant', 'system']);
            $table->text('message_content');
            $table->unsignedInteger('message_tokens')->nullable();
            
            $table->string('detected_intent', 100)->nullable();
            $table->decimal('intent_confidence', 5, 4)->nullable();
            $table->json('entities_extracted')->nullable();
            
            $table->foreignId('context_property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('context_search_query', 255)->nullable();
            
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedTinyInteger('user_satisfaction_rating')->nullable();
            $table->boolean('was_helpful')->nullable();
            
            $table->boolean('handoff_to_human')->default(false);
            $table->text('handoff_reason')->nullable();
            $table->timestamp('handoff_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('model_used', 50)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->decimal('cost_usd', 8, 6)->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('user_id');
            $table->index('session_id');
            $table->index('detected_intent');
            $table->index('created_at');
        });

        Schema::create('chatbot_knowledge', function (Blueprint $table) {
            $table->id();
            $table->enum('category', [
                'faq', 'policy', 'property_guide', 'escrow_guide',
                'verification_guide', 'agent_guide'
            ]);
            $table->text('question');
            $table->text('answer');
            $table->json('keywords');
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('chatbot_knowledge');
    }
};