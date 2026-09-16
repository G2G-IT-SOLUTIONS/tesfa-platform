<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentToOwnContract extends Model
{
    protected $fillable = [
        'contract_number','property_id','buyer_id','seller_id','property_price',
        'down_payment','down_payment_pct','monthly_payment','duration_months',
        'total_cost','remaining_balance','amount_paid','payments_made',
        'payments_remaining','status','start_date','end_date','next_payment_date',
        'completed_at','defaulted_at','credit_score_at_approval','credit_assessment',
        'missed_payments','late_payments','default_amount','contract_pdf_path',
        'signed_documents',
    ];

    protected $casts = [
        'credit_assessment' => 'array',
        'signed_documents' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_payment_date' => 'date',
        'completed_at' => 'datetime',
        'defaulted_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function payments() { return $this->hasMany(RentToOwnPayment::class, 'contract_id'); }
}