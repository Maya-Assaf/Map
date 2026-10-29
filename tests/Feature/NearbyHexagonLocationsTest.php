<?php

namespace Tests\Feature;

use App\Models\Aspect;
use App\Models\Category;
use App\Models\Location;
use App\Models\LocationPath;
use App\Models\LocationPoint;
use App\Models\LocationPolygon;
use App\Models\LocationSpace;
use App\Models\SubAspect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NearbyHexagonLocationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Aspect $aspect;
    private SubAspect $subAspect;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test Explorer',
            'email' => 'explorer@example.com',
            'password' => bcrypt('password123'),
            'position' => 'Senior leader',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'technology and infrastructure',
            'is_verified' => true,
        ]);

        $this->aspect = Aspect::create(['name' => 'Infrastructure']);
        $this->subAspect = SubAspect::create([
            'name' => 'Public Services',
            'aspect_id' => $this->aspect->id,
        ]);
        $this->category = Category::create([
            'name' => 'Hospital',
            'sub_aspect_id' => $this->subAspect->id,
        ]);
    }

    private function createPointLocation(string $name, float $lat, float $lng, ?Category $category = null): Location
    {
        $point = LocationPoint::create([
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        return $point->locatable()->create([
            'name' => $name,
            'description' => "Marker at ($lat, $lng)",
            'aspect_id' => $this->aspect->id,
            'sub_aspect_id' => $this->subAspect->id,
            'category_id' => ($category ?? $this->category)->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_validation_fails_when_coordinates_are_missing_or_invalid(): void
    {
        $response = $this->getJson('/api/public/locations/nearby-hex');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        $invalidResponse = $this->getJson('/api/public/locations/nearby-hex?latitude=105&longitude=200&rings=20');
        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude', 'rings']);
    }

    public function test_returns_nearby_markers_partitioned_into_hexagons_with_pagination(): void
    {
        // Origin: Damascus center (33.5138, 36.2765)
        $originLat = 33.5138;
        $originLng = 36.2765;

        // 1. Very close marker inside Center Hexagon (ring 0, ~100m away)
        $locCenter1 = $this->createPointLocation('Damascus Hospital 1', 33.5142, 36.2768);

        // 2. Another marker inside Center Hexagon (ring 0, ~350m away)
        $locCenter2 = $this->createPointLocation('Damascus Clinic 2', 33.5165, 36.2780);

        // 3. Marker in adjacent Ring 1 Hexagon (~1.4km North)
        $locRing1 = $this->createPointLocation('North Pharmacy Ring 1', 33.5265, 36.2765);

        // 4. Polygon marker with vertices inside Ring 1 (~1.2km East)
        $polygon = LocationPolygon::create();
        $polygon->points()->createMany([
            ['latitude' => 33.5138, 'longitude' => 36.2895],
            ['latitude' => 33.5145, 'longitude' => 36.2905],
            ['latitude' => 33.5130, 'longitude' => 36.2900],
        ]);
        $locPolygonRing1 = $polygon->locatable()->create([
            'name' => 'East Public Park Polygon',
            'description' => 'Polygon inside ring 1',
            'aspect_id' => $this->aspect->id,
            'sub_aspect_id' => $this->subAspect->id,
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
        ]);

        // 5. Distant marker far outside the hex grid (Aleppo: ~300km away)
        $this->createPointLocation('Distant Aleppo Marker', 36.2021, 37.1343);

        // 6. Marker ~5km away (outside rings=1 when hex_size_km=1.0)
        $this->createPointLocation('Outskirts Marker 5km', 33.5600, 36.2765);

        // Request page 1 with per_page = 2, hex_size_km = 1.0, rings = 1
        $response = $this->getJson('/api/public/locations/nearby-hex?' . http_build_query([
            'latitude' => $originLat,
            'longitude' => $originLng,
            'hex_size_km' => 1.0,
            'rings' => 1,
            'per_page' => 2,
            'page' => 1,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('grid_metadata.total_hexagons_in_grid', 7)
            ->assertJsonPath('grid_metadata.occupied_hexagons_count', 3)
            ->assertJsonPath('grid_metadata.center_hexagon.hex_id', 'hex_q0_r0')
            ->assertJsonPath('grid_metadata.center_hexagon.markers_count', 2)
            ->assertJsonPath('pagination.total', 4)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2);

        $page1Data = $response->json('locations.data');
        $this->assertCount(2, $page1Data);

        // Closest two markers in Ring 0 must come first in order of distance
        $this->assertSame($locCenter1->id, $page1Data[0]['id']);
        $this->assertSame(0, $page1Data[0]['hexagon']['ring']);
        $this->assertSame('hex_q0_r0', $page1Data[0]['hexagon']['hex_id']);

        $this->assertSame($locCenter2->id, $page1Data[1]['id']);
        $this->assertSame(0, $page1Data[1]['hexagon']['ring']);

        // Request Page 2
        $responsePage2 = $this->getJson('/api/public/locations/nearby-hex?' . http_build_query([
            'latitude' => $originLat,
            'longitude' => $originLng,
            'hex_size_km' => 1.0,
            'rings' => 1,
            'per_page' => 2,
            'page' => 2,
        ]));

        $responsePage2->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 2);

        $page2Data = $responsePage2->json('locations.data');
        $this->assertCount(2, $page2Data);
        $this->assertSame($locPolygonRing1->id, $page2Data[0]['id']);
        $this->assertSame(1, $page2Data[0]['hexagon']['ring']);
        $this->assertSame($locRing1->id, $page2Data[1]['id']);
        $this->assertSame(1, $page2Data[1]['hexagon']['ring']);
    }

    public function test_authenticated_nearby_hex_endpoint_works_with_get_and_supports_filters(): void
    {
        $originLat = 33.5138;
        $originLng = 36.2765;

        $otherCategory = Category::create([
            'name' => 'School',
            'sub_aspect_id' => $this->subAspect->id,
        ]);

        $hospitalLoc = $this->createPointLocation('Central Hospital', 33.5140, 36.2766, $this->category);
        $this->createPointLocation('Central School', 33.5141, 36.2767, $otherCategory);

        // Unauthenticated request to protected route should return 401
        $unauthResponse = $this->getJson('/api/locations/nearby-hex?' . http_build_query([
            'latitude' => $originLat,
            'longitude' => $originLng,
        ]));
        $unauthResponse->assertStatus(401);

        // Authenticated request with category_id filter via GET
        Sanctum::actingAs($this->user);

        $authResponse = $this->getJson('/api/locations/nearby-hex?' . http_build_query([
            'latitude' => $originLat,
            'longitude' => $originLng,
            'hex_size_km' => 1.0,
            'rings' => 0,
            'category_id' => $this->category->id,
            'per_page' => 10,
        ]));

        $authResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('grid_metadata.total_hexagons_in_grid', 1)
            ->assertJsonPath('pagination.total', 1);

        $items = $authResponse->json('locations.data');
        $this->assertCount(1, $items);
        $this->assertSame($hospitalLoc->id, $items[0]['id']);
    }

    public function test_post_method_is_not_allowed_for_nearby_hex_endpoints(): void
    {
        // Public endpoint rejects POST with 405 Method Not Allowed
        $publicPost = $this->postJson('/api/public/locations/nearby-hex', [
            'latitude' => 33.5138,
            'longitude' => 36.2765,
        ]);
        $publicPost->assertStatus(405);

        // Protected endpoint rejects POST with 405 Method Not Allowed
        Sanctum::actingAs($this->user);
        $protectedPost = $this->postJson('/api/locations/nearby-hex', [
            'latitude' => 33.5138,
            'longitude' => 36.2765,
        ]);
        $protectedPost->assertStatus(405);
    }

    public function test_type_filter_accurately_filters_morph_map_geometry_types(): void
    {
        $originLat = 33.5138;
        $originLng = 36.2765;

        $pointLoc = $this->createPointLocation('Point Marker', 33.5140, 36.2766);

        $space = LocationSpace::create([
            'latitude' => 33.5141,
            'longitude' => 36.2767,
            'radius' => 150.0,
        ]);
        $spaceLoc = $space->locatable()->create([
            'name' => 'Space Marker',
            'description' => 'Space inside ring 0',
            'aspect_id' => $this->aspect->id,
            'sub_aspect_id' => $this->subAspect->id,
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
        ]);

        $path = LocationPath::create();
        $path->points()->createMany([
            ['latitude' => 33.5142, 'longitude' => 36.2768],
            ['latitude' => 33.5145, 'longitude' => 36.2772],
            ['latitude' => 33.5148, 'longitude' => 36.2775],
        ]);
        $pathLoc = $path->locatable()->create([
            'name' => 'Path Marker',
            'description' => 'Path inside ring 0',
            'aspect_id' => $this->aspect->id,
            'sub_aspect_id' => $this->subAspect->id,
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
        ]);

        $polygon = LocationPolygon::create();
        $polygon->points()->createMany([
            ['latitude' => 33.5143, 'longitude' => 36.2769],
            ['latitude' => 33.5146, 'longitude' => 36.2771],
            ['latitude' => 33.5144, 'longitude' => 36.2773],
        ]);
        $polyLoc = $polygon->locatable()->create([
            'name' => 'Polygon Marker',
            'description' => 'Polygon inside ring 0',
            'aspect_id' => $this->aspect->id,
            'sub_aspect_id' => $this->subAspect->id,
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        foreach ([
            'point' => $pointLoc->id,
            'space' => $spaceLoc->id,
            'path' => $pathLoc->id,
            'polygon' => $polyLoc->id,
        ] as $type => $expectedId) {
            $res = $this->getJson('/api/locations/nearby-hex?' . http_build_query([
                'latitude' => $originLat,
                'longitude' => $originLng,
                'hex_size_km' => 1.0,
                'rings' => 1,
                'type' => $type,
            ]));

            $res->assertStatus(200)
                ->assertJsonPath('pagination.total', 1)
                ->assertJsonPath('locations.data.0.id', $expectedId)
                ->assertJsonPath('locations.data.0.locatable_type', $type);
        }
    }

    public function test_protected_endpoint_enforces_verified_and_password_reset_middlewares(): void
    {
        $unverifiedUser = User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
            'position' => 'Volunteer',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'technology and infrastructure',
            'is_verified' => false,
        ]);

        Sanctum::actingAs($unverifiedUser);
        $this->getJson('/api/locations/nearby-hex?latitude=33.5138&longitude=36.2765')
            ->assertStatus(403);

        $resetRequiredUser = User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.com',
            'password' => bcrypt('password123'),
            'position' => 'Volunteer',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'technology and infrastructure',
            'is_verified' => true,
        ]);
        DB::table('users')->where('id', $resetRequiredUser->id)->update(['rest_password' => true]);
        $resetRequiredUser->refresh();

        Sanctum::actingAs($resetRequiredUser);
        $this->getJson('/api/locations/nearby-hex?latitude=33.5138&longitude=36.2765')
            ->assertStatus(405);
    }

    public function test_nearby_hex_executes_with_bounded_queries_and_fast_response_on_large_dataset(): void
    {
        $originLat = 33.5138;
        $originLng = 36.2765;
        $now = now()->toDateTimeString();

        $pointsRows = [];
        $locationsRows = [];

        for ($i = 1; $i <= 1000; $i++) {
            $latOffset = (($i % 40) - 20) * 0.0005;
            $lngOffset = ((intdiv($i, 40) % 40) - 20) * 0.0005;

            $pointsRows[] = [
                'id' => $i,
                'latitude' => round($originLat + $latOffset, 7),
                'longitude' => round($originLng + $lngOffset, 7),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $locationsRows[] = [
                'id' => $i,
                'name' => "Bulk Marker #{$i}",
                'description' => 'Performance test marker',
                'aspect_id' => $this->aspect->id,
                'sub_aspect_id' => $this->subAspect->id,
                'category_id' => $this->category->id,
                'user_id' => $this->user->id,
                'locatable_type' => 'point',
                'locatable_id' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($pointsRows, 500) as $chunk) {
            DB::table('location_points')->insert($chunk);
        }
        foreach (array_chunk($locationsRows, 500) as $chunk) {
            DB::table('locations')->insert($chunk);
        }

        Sanctum::actingAs($this->user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);
        $response = $this->getJson('/api/locations/nearby-hex?' . http_build_query([
            'latitude' => $originLat,
            'longitude' => $originLng,
            'hex_size_km' => 1.0,
            'rings' => 2,
            'per_page' => 15,
            'page' => 1,
        ]));
        $elapsedMs = (microtime(true) - $startTime) * 1000;

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.per_page', 15);

        $this->assertGreaterThan(500, $response->json('pagination.total'));
        $this->assertCount(15, $response->json('locations.data'));
        $this->assertLessThanOrEqual(8, count($queries), 'Expected bounded SQL query count via deferred hydration.');
        $this->assertLessThan(250.0, $elapsedMs, 'Expected nearby-hex response under 250ms for 1,000 markers.');
    }
}

