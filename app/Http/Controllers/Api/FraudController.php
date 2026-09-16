<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\AI\FraudDetectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FraudController extends Controller
{
    public function __construct(
        private FraudDetectionService $fraudService
    ) {}

    /**
     * Scan property for fraud
     */
    public function scan(Request $request, Property $property): JsonResponse
    {
        // Check authorization
        if ($request->user()->id !== $property->owner_id && !$request->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $alert = $this->fraudService->scanProperty($property);

        if ($alert) {
            return response()->json([
                'fraud_detected' => true,
                'alert_id' => $alert->id,
                'severity' => $alert->severity,
                'confidence' => $alert->confidence_score,
                'type' => $alert->alert_type,
                'status' => $alert->status,
            ]);
        }

        return response()->json([
            'fraud_detected' => false,
            'message' => 'No fraud indicators detected',
        ]);
    }
}