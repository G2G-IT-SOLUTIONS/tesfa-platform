<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('model_name', 100);
            $table->string('model_version', 20);
            $table->date('metric_date');
            
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('precision_score', 5, 4)->nullable();
            $table->decimal('recall_score', 5, 4)->nullable();
            $table->decimal('f1_score', 5, 4)->nullable();
            $table->decimal('auc_roc', 5, 4)->nullable();
            $table->decimal('mae', 15, 2)->nullable();
            $table->decimal('rmse', 15, 2)->nullable();
            $table->decimal('mape', 5, 2)->nullable();
            
            $table->unsignedInteger('predictions_count')->default(0);
            $table->unsignedInteger('validated_count')->default(0);
            
            $table->json('confusion_matrix')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->unique(['model_name', 'model_version', 'metric_date'], 'uk_model_metrics');
            $table->index('model_name');
            $table->index('metric_date');
        });

        Schema::create('model_training_logs', function (Blueprint $table) {
            $table->id();
            $table->string('run_id', 50)->unique();
            $table->string('model_name', 100);
            $table->string('model_version', 20);
            $table->string('data_version', 50);
            
            $table->json('hyperparameters');
            $table->date('training_data_start');
            $table->date('training_data_end');
            $table->unsignedInteger('training_samples');
            
            $table->unsignedInteger('duration_seconds');
            $table->enum('status', ['running', 'completed', 'failed', 'cancelled']);
            $table->text('error_message')->nullable();
            
            $table->decimal('final_accuracy', 5, 4)->nullable();
            $table->decimal('final_loss', 10, 6)->nullable();
            
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('model_name');
            $table->index('status');
            $table->index('started_at');
        });

        Schema::create('feature_importance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_name', 100);
            $table->string('model_version', 20);
            $table->string('feature_name', 100);
            $table->decimal('importance_score', 8, 6);
            $table->unsignedInteger('rank');
            $table->date('computed_date');
            $table->timestamps();
            
            $table->index(['model_name', 'model_version'], 'idx_feature_model');
            $table->index('computed_date');
        });

        Schema::create('ab_test_results', function (Blueprint $table) {
            $table->id();
            $table->string('test_id', 50)->unique();
            $table->string('test_name', 255);
            $table->text('description')->nullable();
            
            $table->string('variant_a_name', 100);
            $table->string('variant_b_name', 100);
            $table->json('variant_a_config')->nullable();
            $table->json('variant_b_config')->nullable();
            
            $table->string('metric_name', 100);
            $table->decimal('variant_a_value', 15, 4)->nullable();
            $table->decimal('variant_b_value', 15, 4)->nullable();
            $table->decimal('lift_pct', 5, 2)->nullable();
            $table->decimal('p_value', 8, 6)->nullable();
            $table->boolean('is_significant')->default(false);
            
            $table->unsignedInteger('variant_a_samples')->default(0);
            $table->unsignedInteger('variant_b_samples')->default(0);
            
            $table->enum('status', ['running', 'completed', 'stopped'])->default('running');
            $table->string('winner', 10)->nullable();
            
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('test_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_test_results');
        Schema::dropIfExists('feature_importance_logs');
        Schema::dropIfExists('model_training_logs');
        Schema::dropIfExists('model_performance_metrics');
    }
};