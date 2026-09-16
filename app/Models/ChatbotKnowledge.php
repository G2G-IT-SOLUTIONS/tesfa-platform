<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotKnowledge extends Model
{
    protected $fillable = [
        'category','question','answer','keywords','usage_count','helpful_count',
        'last_used_at','is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}