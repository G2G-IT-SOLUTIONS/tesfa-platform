<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id','consent_type','granted','granted_at','revoked_at',
        'ip_address','user_agent','consent_version','withdrawal_reason','withdrawn_by',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
}