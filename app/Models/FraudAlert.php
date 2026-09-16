<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudAlert extends Model
{
    protected $fillable = [
        'alert_type','severity','target_type','target_id','target_type_id',
        'detection_method','ml_model_version','confidence_score','evidence',
        'evidence_screenshots','status','assigned_to','reviewed_at','review_notes',
        'resolution','resolved_at','resolved_by','actions_taken','user_notified',
        'admin_notified','law_enforcement_notified','feedback_loop_status',
        'model_retrain_triggered',
    ];

    protected $casts = [
        'evidence' => 'array',
        'evidence_screenshots' => 'array',
        'actions_taken' => 'array',
        'confidence_score' => 'decimal:4',
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'user_notified' => 'boolean',
        'admin_notified' => 'boolean',
        'law_enforcement_notified' => 'boolean',
        'model_retrain_triggered' => 'boolean',
    ];

    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function resolvedBy() { return $this->belongsTo(User::class, 'resolved_by'); }
}