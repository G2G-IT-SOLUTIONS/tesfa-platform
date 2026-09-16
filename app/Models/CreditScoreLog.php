<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditScoreLog extends Model
{
    protected $fillable = [
        'credit_score_id','user_id','old_score','new_score','score_change',
        'change_reason','change_details','triggered_by','triggered_by_id','ip_address',
    ];

    protected $casts = [
        'change_details' => 'array',
        'old_score' => 'integer',
        'new_score' => 'integer',
        'score_change' => 'integer',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    public function creditScore() { return $this->belongsTo(CreditScore::class); }
    public function user() { return $this->belongsTo(User::class); }
}