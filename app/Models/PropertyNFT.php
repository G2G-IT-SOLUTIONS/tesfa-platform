<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyNFT extends Model
{
    protected $table = 'property_nfts';
    protected $fillable = [
        'property_id','token_id','contract_address','owner_address','metadata_uri',
        'minted_at','chain',
    ];

    protected $casts = [
        'minted_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }
}