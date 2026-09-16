<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyVerification extends Model
{
    protected $fillable = [
        'property_id','agent_id','job_number','status','scheduled_at',
        'started_at','completed_at','agent_report','agent_photos',
        'agent_video_path','agent_notes','agent_gps_at_start',
        'agent_gps_at_property','gps_distance_meters','documents_submitted',
        'documents_verified','document_verification_notes','condition_rating',
        'condition_notes','dispute_check_passed','dispute_check_notes',
        'blockchain_tx_hash','blockchain_anchored_at','reviewed_by','review_notes',
        'base_fee','distance_bonus','urgency_bonus','quality_bonus','total_earnings',
    ];

    protected $casts = [
        'agent_report' => 'array',
        'agent_photos' => 'array',
        'documents_submitted' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'blockchain_anchored_at' => 'datetime',
        'documents_verified' => 'boolean',
        'dispute_check_passed' => 'boolean',
    ];

    public function property() { return $this->belongsTo(Property::class); }
    public function agent() { return $this->belongsTo(User::class, 'agent_id'); }
    public function reviewedBy() { return $this->belongsTo(User::class, 'reviewed_by'); }
}