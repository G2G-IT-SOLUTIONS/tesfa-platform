<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use App\Services\AI\FraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_listing_detected(): void
    {
        $user = User::factory()->create();

        // Create original property
        $original = Property::factory()->create([
            'owner_id' => $user->id,
            'location' => new \MatanYadaev\EloquentSpatial\Objects\Point(9.0108, 38.7613),
            'area_sqm' => 100,
        ]);

        // Create duplicate (same location, similar area)
        $duplicate = Property::factory()->create([
            'owner_id' => $user->id,
            'location' => new \MatanYadaev\EloquentSpatial\Objects\Point(9.0108, 38.7613),
            'area_sqm' => 105,
        ]);

        $service = app(FraudDetectionService::class);
        $alert = $service->scanProperty($duplicate);

        $this->assertNotNull($alert);
        $this->assertEquals('duplicate_listing', $alert->alert_type);
    }

    public function test_price_anomaly_detected(): void
    {
        $user = User::factory()->create();

        // Create normal priced properties
        Property::factory()->count(5)->create([
            'owner_id' => $user->id,
            'neighborhood' => 'Bole',
            'type' => 'apartment',
            'area_sqm' => 100,
            'price' => 5000000,
        ]);

        // Create anomaly (10x price)
        $anomaly = Property::factory()->create([
            'owner_id' => $user->id,
            'neighborhood' => 'Bole',
            'type' => 'apartment',
            'area_sqm' => 100,
            'price' => 50000000,
        ]);

        $service = app(FraudDetectionService::class);
        $alert = $service->scanProperty($anomaly);

        $this->assertNotNull($alert);
        $this->assertEquals('price_anomaly', $alert->alert_type);
    }
}