<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageQualityAssessment extends Model
{
    protected $fillable = [
        'property_id','image_path','overall_score','resolution_score','blur_score',
        'lighting_score','composition_score','rekognition_labels','detected_rooms',
        'detected_objects','has_faces','perceptual_hash','duplicate_of','status',
        'rejection_reasons','improvement_suggestions','seller_action','seller_action_at',
    ];

    protected $casts = [
        'rekognition_labels' => 'array',
        'detected_rooms' => 'array',
        'detected_objects' => 'array',
        'rejection_reasons' => 'array',
        'improvement_suggestions' => 'array',
        'has_faces' => 'boolean',
        'seller_action_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }
}