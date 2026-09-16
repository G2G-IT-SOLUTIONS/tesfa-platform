<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','tier','license_number','license_expires_at','coverage_area',
        'specializations','total_verifications','successful_verifications',
        'disputed_verifications','average_rating','total_ratings',
        'total_earnings','pending_earnings','base_fee','is_available',
        'is_active','last_job_at','current_gps',
    ];

    protected $casts = [
        'specializations' => 'array',
        'license_expires_at' => 'date',
        'last_job_at' => 'datetime',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'average_rating' => 'decimal:2',
    ];

    public function user() { return $this->belongsTo(User::class); }
}