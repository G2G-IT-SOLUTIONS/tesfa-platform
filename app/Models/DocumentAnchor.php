<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAnchor extends Model
{
    protected $fillable = [
        'document_id','blockchain','tx_hash','block_number','anchored_at','metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'anchored_at' => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
}