<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_health_logs', function (Blueprint $table) {
            $table->id();
            
            $table->enum('metric_name', [
                'response_time', 'error_rate', 'cpu_usage', 'memory_usage',
                'db_connections', 'queue_size', 'disk_space', 'cache_hit_rate',
                'throughput_rps', 'ssl_expiry_days', 'backup_status'
            ]);
            $table->decimal('metric_value', 10, 2);
            $table->enum('metric_unit', ['ms', 'percent', 'count', 'mb', 'gb', 'ratio', 'days']);
            
            $table->decimal('warning_threshold', 10, 2);
            $table->decimal('critical_threshold', 10, 2);
            
            $table->string('endpoint', 255)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_type', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->text('stack_trace')->nullable();
            
            $table->string('server_id', 100);
            $table->string('server_region', 100)->default('Addis_Ababa');
            
            $table->decimal('baseline_value', 10, 2)->nullable();
            $table->boolean('is_anomaly')->default(false);
            $table->decimal('anomaly_score', 5, 4)->nullable();
            
            $table->boolean('alert_sent')->default(false);
            $table->enum('alert_channel', ['email', 'sms', 'pagerduty', 'slack', 'webhook'])->nullable();
            $table->boolean('alert_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('metric_name');
            $table->index('endpoint');
            $table->index('is_anomaly');
            $table->index(['alert_sent', 'alert_acknowledged']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_health_logs');
    }
};