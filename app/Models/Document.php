<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id','property_id','verification_id','type','original_filename',
        'storage_path','mime_type','file_size','sha256_hash','ipfs_hash','status',
        'verified_by','verified_at','verification_notes','issue_date','expiry_date',
        'issuing_authority','document_number','metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function property() { return $this->belongsTo(Property::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
}