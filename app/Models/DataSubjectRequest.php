<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSubjectRequest extends Model
{
    protected $fillable = [
        'request_number','user_id','request_type','request_description','status',
        'received_at','due_at','completed_at','extension_reason',
        'response_summary','response_data_size_mb','response_file_path',
        'rejection_reason','rejection_legal_basis','handled_by',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
}