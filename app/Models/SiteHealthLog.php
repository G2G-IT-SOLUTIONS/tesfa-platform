<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteHealthLog extends Model
{
    protected $fillable = [
        'metric_name','metric_value','metric_unit','warning_threshold',
        'critical_threshold','endpoint','http_status','error_type','error_message',
        'stack_trace','server_id','server_region','baseline_value','is_anomaly',
        'anomaly_score','alert_sent','alert_channel','alert_acknowledged',
        'acknowledged_by','acknowledged_at','resolved_at','resolution_notes',
    ];

    protected $casts = [
        'is_anomaly' => 'boolean',
        'alert_sent' => 'boolean',
        'alert_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;
}