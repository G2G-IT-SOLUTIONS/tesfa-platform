<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainTransaction extends Model
{
    protected $fillable = [
        'tx_hash','network','from_address','to_address','value','gas_price',
        'gas_used','block_number','status','related_type','related_id','metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}