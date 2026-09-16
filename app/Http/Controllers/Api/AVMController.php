<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\AI\AVMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AVMController extends Controller
{
    public function __construct(
        private AVMService $avmService
    ) {}

    /**
     * Get valuation for property
     */
    public function valuate(Request $request, Property $property): JsonResponse
    {
        $valuation = $this->avmService->valuate($property);

        return response()->json([
            'property_id' => $property->id,
            'estimated_value' => $valuation->estimated_value,
            'value_range' => [
                'low' => $valuation->value_range_low,
                'high' => $valuation->value_range_high,
            ],
            'confidence' => $valuation->confidence_score,
            'comparable_properties' => $valuation->comparable_properties,
            'market_conditions' => $valuation->market_conditions,
            'model_version' => $valuation->model_version,
            'computed_at' => $valuation->computed_at->toIso8601String(),
        ]);
    }
}