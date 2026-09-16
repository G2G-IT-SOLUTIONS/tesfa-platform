<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentToOwnPayment extends Model
{
    protected $fillable = [
        'contract_id','buyer_id','payment_number','amount_due','amount_paid',
        'late_fee','due_date','paid_at','status','payment_method',
        'transaction_reference','notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function contract() { return $this->belongsTo(RentToOwnContract::class, 'contract_id'); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
}