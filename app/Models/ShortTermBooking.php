<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortTermBooking extends Model
{
    protected $fillable = [
        'booking_number','property_id','guest_id','host_id','check_in','check_out',
        'nights','guests','nightly_rate','subtotal','cleaning_fee','service_fee',
        'security_deposit','total_amount','host_payout','platform_fee','currency',
        'status','payment_status','payment_reference','paid_at','cancelled_at',
        'cancellation_reason','cancelled_by','refund_amount','guest_reviewed_at',
        'host_reviewed_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'guest_reviewed_at' => 'datetime',
        'host_reviewed_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }
    public function guest() { return $this->belongsTo(User::class, 'guest_id'); }
    public function host() { return $this->belongsTo(User::class, 'host_id'); }
}