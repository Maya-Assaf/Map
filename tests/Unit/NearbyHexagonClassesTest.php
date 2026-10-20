<?php

namespace Tests\Unit;

use App\Http\Requests\NearbyHexagonRequest;
use App\Http\Resources\NearbyHexagonResponse;
use App\Http\Resources\NearbyLocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class NearbyHexagonClassesTest extends TestCase
{
    public function test_nearby_hexagon_request_rules_and_authorization(): void
    {
        $request = new NearbyHexagonRequest();

        $this->assertTrue($request->authorize());

        $rules = $request->rules();
        $this->assertArrayHasKey('latitude', $rules);
        $this->assertArrayHasKey('longitude', $rules);
        $this->assertArrayHasKey('hex_size_km', $rules);
        $this->assertArrayHasKey('rings', $rules);
        $this->assertArrayHasKey('per_page', $rules);
        $this->assertArrayHasKey('page', $rules);
    }

    public function test_nearby_location_resource_transforms_data_correctly(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Test Location',
            'description' => 'Test Desc',
            'aspect_id' => 1,
            'aspect' => 'Test Aspect',
            'sub_aspect_id' => 2,
            'sub_aspect' => 'Test SubAspect',
            'category_id' => 3,
            'category' => 'Test Category',
            'user_id' => 10,
            'user_layer' => 'IT',
            'locatable_type' => 'App\\Models\\LocationPoint',
            'locatable' => null,
            'images' => [],
            'references' => [],
            'representative_point' => ['latitude' => 33.5, 'longitude' => 36.2],
            'distance_km' => 0.45,
            'hexagon' => ['hex_id' => 'hex_q0_r0', 'ring' => 0],
            'created_at' => null,
            'updated_at' => null,
        ];

        $resource = new NearbyLocationResource($data);
        $transformed = $resource->toArray(Request::create('/api/public/locations/nearby-hex'));

        $this->assertSame(42, $transformed['id']);
        $this->assertSame('Test Location', $transformed['name']);
        $this->assertSame(0.45, $transformed['distance_km']);
        $this->assertSame('hex_q0_r0', $transformed['hexagon']['hex_id']);
    }

    public function test_nearby_hexagon_response_to_response(): void
    {
        $response = new NearbyHexagonResponse(
            gridMetadata: ['total_hexagons_in_grid' => 7],
            pagination: ['total' => 10, 'per_page' => 5],
            locations: ['data' => []]
        );

        $jsonResponse = $response->toResponse(Request::create('/api/public/locations/nearby-hex'));

        $this->assertInstanceOf(JsonResponse::class, $jsonResponse);
        $this->assertSame(200, $jsonResponse->getStatusCode());

        $content = $jsonResponse->getData(true);
        $this->assertTrue($content['success']);
        $this->assertSame(7, $content['grid_metadata']['total_hexagons_in_grid']);
        $this->assertSame(10, $content['pagination']['total']);
    }
}
