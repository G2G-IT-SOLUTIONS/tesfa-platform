<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\AI\FraudDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectFraudPatternsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $propertyId = null,
        public int $batchSize = 100
    ) {}

    public function handle(FraudDetectionService $service): void
    {
        $query = Property::where('status', 'active');

        if ($this->propertyId) {
            $query->where('id', $this->propertyId);
        }

        $properties = $query->limit($this->batchSize)->get();

        foreach ($properties as $property) {
            $service->scanProperty($property);
        }
    }
}