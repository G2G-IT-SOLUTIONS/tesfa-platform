<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentOCRResult extends Model
{
    protected $table = 'document_ocr_results';
    protected $fillable = [
        'document_id','ocr_engine','ocr_confidence','processing_time_ms',
        'extracted_text','extracted_fields','validation_status','validation_errors',
        'validated_by','validated_at','auto_filled_fields','manual_corrections',
        'time_saved_minutes',
    ];

    protected $casts = [
        'extracted_fields' => 'array',
        'validation_errors' => 'array',
        'auto_filled_fields' => 'array',
        'manual_corrections' => 'array',
        'validated_at' => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
}