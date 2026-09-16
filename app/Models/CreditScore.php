<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','score','score_tier','payment_history_score','amounts_owed_score',
        'credit_history_length_score','credit_mix_score','new_credit_score',
        'rent_to_own_payments_on_time','rent_to_own_payments_total',
        'rent_to_own_payments_late','rent_to_own_payments_missed',
        'rent_to_own_default_amount','telebirr_transaction_volume',
        'telebirr_transaction_count','platform_engagement_months',
        'platform_verified_transactions','community_references_count',
        'community_references_positive','employment_verified',
        'employment_duration_months','employer_name','monthly_income_verified',
        'score_change_last_30_days','score_change_last_90_days','score_history',
        'status','frozen_reason','frozen_at','frozen_by','model_version',
        'computed_at','next_computation_at','computation_trigger',
    ];

    protected $casts = [
        'score' => 'integer',
        'score_history' => 'array',
        'computed_at' => 'datetime',
        'next_computation_at' => 'datetime',
        'frozen_at' => 'datetime',
        'employment_verified' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function logs() { return $this->hasMany(CreditScoreLog::class); }
}