<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataProcessingLog extends Model
{
    protected $fillable = [
        'user_id','processing_activity','legal_basis','data_categories',
        'retention_period_days','third_parties_shared','processed_in',
        'data_transfer_outside_eea','transfer_safeguards',
    ];

    protected $casts = [
        'data_categories' => 'array',
        'third_parties_shared' => 'array',
        'data_transfer_outside_eea' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
}