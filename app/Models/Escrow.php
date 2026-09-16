<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    protected $fillable = [
        'escrow_number','property_id','buyer_id','seller_id','agent_id','concierge_id',
        'contract_address','contract_deployed_at','property_price','currency',
        'platform_fee_pct','agent_fee_pct','concierge_fee','platform_fee_amount',
        'agent_fee_amount','seller_amount','deposit_amount','deposit_currency',
        'deposit_method','deposit_tx_hash','deposited_at','deposit_confirmed',
        'status','seller_documents','buyer_documents','document_hashes',
        'verification_id','verification_completed_at','dispute_window_hours',
        'dispute_window_starts','dispute_window_ends','dispute_raised_at',
        'dispute_reason','dispute_resolved_at','dispute_resolution',
        'dispute_resolved_by','funded_at','documents_submitted_at','verified_at',
        'buyer_confirmed_at','completed_at','refunded_at','cancelled_at','cancelled_reason',
    ];

    protected $casts = [
        'seller_documents' => 'array',
        'buyer_documents' => 'array',
        'document_hashes' => 'array',
        'deposit_confirmed' => 'boolean',
        'contract_deployed_at' => 'datetime',
        'deposited_at' => 'datetime',
        'verification_completed_at' => 'datetime',
        'dispute_window_starts' => 'datetime',
        'dispute_window_ends' => 'datetime',
        'dispute_raised_at' => 'datetime',
        'dispute_resolved_at' => 'datetime',
        'funded_at' => 'datetime',
        'documents_submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'buyer_confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function agent() { return $this->belongsTo(User::class, 'agent_id'); }
    public function concierge() { return $this->belongsTo(User::class, 'concierge_id'); }
}