<?php

namespace Tests\Feature;

use App\Http\Controllers\LocationController;
use App\Models\Aspect;
use App\Models\Category;
use App\Models\Location;
use App\Models\LocationImage;
use App\Models\LocationPath;
use App\Models\LocationPoint;
use App\Models\LocationPolygon;
use App\Models\SubAspect;
use App\Models\User;
use Database\Seeders\FixImportedLocationsGeometrySeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FixImportedLocationsGeometrySeederTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Map Seeder Tester',
            'email' => 'map-seeder@example.com',
            'password' => bcrypt('password123'),
            'position' => 'Head',
            'department' => 'ADMIN DEPARTMENT',
            'layer' => 'urban planning',
            'is_verified' => true,
        ]);

        $this->seed(LocationSeeder::class);
    }

    public function test_fixes_bowtie_self_intersecting_polygons_and_removes_duplicate_closing_vertices(): void
    {
        $urban = Aspect::where('name', 'Urban Planning')->firstOrFail();
        $housingSub = SubAspect::where('aspect_id', $urban->id)->where('name', 'Housing & Buildings')->firstOrFail();
        $resBlockCat = Category::where('sub_aspect_id', $housingSub->id)->where('name', 'Residential block')->firstOrFail();

        // Insert a 5-vertex rectangle in twisted/hourglass order: SW -> SE -> NW -> NE -> SW
        $poly = LocationPolygon::create();
        $poly->points()->createMany([
            ['latitude' => 33.5246140, 'longitude' => 36.3194916], // SW
            ['latitude' => 33.5246140, 'longitude' => 36.3201382], // SE
            ['latitude' => 33.5251530, 'longitude' => 36.3194916], // NW (crosses diagonally!)
            ['latitude' => 33.5251530, 'longitude' => 36.3201382], // NE
            ['latitude' => 33.5246140, 'longitude' => 36.3194916], // Duplicate closing SW
        ]);

        $location = $poly->locatable()->create([
            'name' => 'Twisted Building Polygon',
            'user_id' => $this->user->id,
            'aspect_id' => $urban->id,
            'sub_aspect_id' => $housingSub->id,
            'category_id' => $resBlockCat->id,
        ]);

        $this->seed(FixImportedLocationsGeometrySeeder::class);

        $poly->refresh();
        $pts = $poly->points;

        // Closing duplicate removed => 4 unique perimeter vertices
        $this->assertCount(4, $pts);

        // IDs strictly increase
        $ids = $pts->pluck('id')->all();
        $sortedIds = $ids;
        sort($sortedIds);
        $this->assertSame($sortedIds, $ids);

        // Exact CCW perimeter order: SW -> SE -> NE -> NW
        $coords = $pts->map(fn($p) => ['lat' => $p->latitude, 'lon' => $p->longitude])->all();
        $this->assertFalse(FixImportedLocationsGeometrySeeder::isPolygonSelfIntersecting($coords));
        $this->assertEqualsWithDelta(33.5246140, $coords[0]['lat'], 1e-7);
        $this->assertEqualsWithDelta(36.3194916, $coords[0]['lon'], 1e-7);
        $this->assertEqualsWithDelta(33.5246140, $coords[1]['lat'], 1e-7);
        $this->assertEqualsWithDelta(36.3201382, $coords[1]['lon'], 1e-7);
        $this->assertEqualsWithDelta(33.5251530, $coords[2]['lat'], 1e-7);
        $this->assertEqualsWithDelta(36.3201382, $coords[2]['lon'], 1e-7);
        $this->assertEqualsWithDelta(33.5251530, $coords[3]['lat'], 1e-7);
        $this->assertEqualsWithDelta(36.3194916, $coords[3]['lon'], 1e-7);

        // Verify public API returns the ordered vertices
        $response = $this->getJson('/api/public/locations?name=Twisted+Building+Polygon');
        $response->assertStatus(200)
            ->assertJsonCount(4, 'locations.0.locatable.points');

        $apiPts = $response->json('locations.0.locatable.points');
        $this->assertEqualsWithDelta(36.3201382, $apiPts[2]['longitude'], 1e-7);
        $this->assertEqualsWithDelta(33.5251530, $apiPts[2]['latitude'], 1e-7);
    }

    public function test_untangles_crossing_path_points_and_removes_consecutive_duplicates(): void
    {
        $urban = Aspect::where('name', 'Urban Planning')->firstOrFail();
        $transSub = SubAspect::where('aspect_id', $urban->id)->where('name', 'Transport & Connectivity')->firstOrFail();
        $roadCat = Category::where('sub_aspect_id', $transSub->id)->where('name', 'Classified road network')->firstOrFail();

        // Create a crossing figure-8 open path: (0,0) -> (2,2) -> (2,0) -> (0,2) in Damascus offsets + duplicate
        $path = LocationPath::create();
        $path->points()->createMany([
            ['latitude' => 33.5100, 'longitude' => 36.2900],
            ['latitude' => 33.5100, 'longitude' => 36.2900], // consecutive duplicate
            ['latitude' => 33.5120, 'longitude' => 36.2920], // crosses segment (36.2920,33.5100)->(36.2900,33.5120)
            ['latitude' => 33.5100, 'longitude' => 36.2920],
            ['latitude' => 33.5120, 'longitude' => 36.2900],
        ]);

        $path->locatable()->create([
            'name' => 'Crossing Path Test',
            'user_id' => $this->user->id,
            'aspect_id' => $urban->id,
            'sub_aspect_id' => $transSub->id,
            'category_id' => $roadCat->id,
        ]);

        $this->seed(FixImportedLocationsGeometrySeeder::class);

        $path->refresh();
        $pts = $path->points;
        $this->assertCount(4, $pts);

        $coords = $pts->map(fn($p) => ['lat' => $p->latitude, 'lon' => $p->longitude])->all();
        $this->assertFalse(FixImportedLocationsGeometrySeeder::isOpenPathSelfIntersecting($coords));
    }

    public function test_groups_and_orders_scattered_river_and_street_records_into_smooth_paths(): void
    {
        $riverAspect = Aspect::create(['name' => 'River']);
        $riverSub = SubAspect::create(['name' => 'River', 'aspect_id' => $riverAspect->id]);
        $riverRawCat = Category::create(['name' => 'Waterway River', 'sub_aspect_id' => $riverSub->id]);

        // Insert 5 out-of-order points along Barada River (jumping East & West)
        $shuffledLons = [36.2940, 36.2900, 36.2980, 36.2920, 36.2960];
        foreach ($shuffledLons as $idx => $lon) {
            $pt = LocationPoint::create([
                'latitude' => 33.5120,
                'longitude' => $lon,
            ]);
            $loc = $pt->locatable()->create([
                'name' => 'نهر بردى',
                'user_id' => $this->user->id,
                'aspect_id' => $riverAspect->id,
                'sub_aspect_id' => $riverSub->id,
                'category_id' => $riverRawCat->id,
            ]);

            if ($idx === 3) {
                LocationImage::create([
                    'location_id' => $loc->id,
                    'image_path' => 'imgs/barada_test.jpg',
                ]);
            }
        }

        // Insert 4 synthetic 20mx20m polygon street boxes (mimicking cleaned_chunk_51.json) in scrambled order
        $streetAspect = Aspect::create(['name' => 'Street']);
        $streetSub = SubAspect::create(['name' => 'Street And Road', 'aspect_id' => $streetAspect->id]);
        $hwPrimaryCat = Category::create(['name' => 'Highway Primary', 'sub_aspect_id' => $streetSub->id]);

        foreach ([36.2970, 36.2930, 36.2990, 36.2950] as $lon) {
            $lat = 33.4958;
            $poly = LocationPolygon::create();
            $poly->points()->createMany([
                ['latitude' => $lat - 0.0001, 'longitude' => $lon - 0.0001],
                ['latitude' => $lat - 0.0001, 'longitude' => $lon + 0.0001],
                ['latitude' => $lat + 0.0001, 'longitude' => $lon + 0.0001],
                ['latitude' => $lat + 0.0001, 'longitude' => $lon - 0.0001],
                ['latitude' => $lat - 0.0001, 'longitude' => $lon - 0.0001],
            ]);
            $poly->locatable()->create([
                'name' => 'شارع خالد إبن الوليد',
                'user_id' => $this->user->id,
                'aspect_id' => $streetAspect->id,
                'sub_aspect_id' => $streetSub->id,
                'category_id' => $hwPrimaryCat->id,
            ]);
        }

        $this->seed(FixImportedLocationsGeometrySeeder::class);

        // 1. Verify Barada River became a single ordered LocationPath under Water network
        $baradaLocations = Location::where('name', 'نهر بردى')->with(['category', 'locatable', 'images'])->get();
        $this->assertCount(1, $baradaLocations);

        $baradaLoc = $baradaLocations->first();
        $this->assertSame('path', $baradaLoc->locatable_type);
        $this->assertSame('Water network', $baradaLoc->category->name);
        $this->assertCount(1, $baradaLoc->images);
        $this->assertCount(5, $baradaLoc->locatable->points);

        $baradaLons = $baradaLoc->locatable->points->pluck('longitude')->all();
        $this->assertSame([36.29, 36.292, 36.294, 36.296, 36.298], $baradaLons);

        // 2. Verify Khaled Ibn Al Walid Street became a single ordered LocationPath under Classified road network
        $streetLocations = Location::where('name', 'شارع خالد إبن الوليد')->with(['category', 'locatable'])->get();
        $this->assertCount(1, $streetLocations);

        $streetLoc = $streetLocations->first();
        $this->assertSame('path', $streetLoc->locatable_type);
        $this->assertSame('Classified road network', $streetLoc->category->name);
        $this->assertCount(4, $streetLoc->locatable->points);

        $streetLons = $streetLoc->locatable->points->pluck('longitude')->all();
        $this->assertSame([36.293, 36.295, 36.297, 36.299], $streetLons);

        // Synthetic street polygons & old merged river points must be completely cleaned up
        $this->assertSame(0, LocationPolygon::count());
        $this->assertSame(0, DB::table('location_polygon_points')->count());
        $this->assertSame(0, LocationPoint::count());
    }

    public function test_synchronizes_all_imported_categories_with_controller_marker_types_and_is_idempotent(): void
    {
        $social = Aspect::where('name', 'Social')->firstOrFail();
        $eduSub = SubAspect::where('aspect_id', $social->id)->where('name', 'Education System')->firstOrFail();
        $schoolCat = Category::where('sub_aspect_id', $eduSub->id)->where('name', 'School')->firstOrFail();

        // Point school assigned to polygon-only 'School' category (mimicking chunk_52_cleaned.json)
        $ptInstitute = LocationPoint::create(['latitude' => 33.4917, 'longitude' => 36.2466]);
        $locInstitute = $ptInstitute->locatable()->create([
            'name' => 'معهد الرياضة للبنات',
            'user_id' => $this->user->id,
            'aspect_id' => $social->id,
            'sub_aspect_id' => $eduSub->id,
            'category_id' => $schoolCat->id,
        ]);

        $ptBasicSchool = LocationPoint::create(['latitude' => 33.4949, 'longitude' => 36.2726]);
        $locBasicSchool = $ptBasicSchool->locatable()->create([
            'name' => 'مدرسة سعد بن معاذ',
            'user_id' => $this->user->id,
            'aspect_id' => $social->id,
            'sub_aspect_id' => $eduSub->id,
            'category_id' => $schoolCat->id,
        ]);

        // Polygon restaurant assigned to raw 'Restaurant' category (mimicking cleaned_chunk_51.json)
        $urban = Aspect::where('name', 'Urban Planning')->firstOrFail();
        $amenSub = SubAspect::where('aspect_id', $urban->id)->where('name', 'Amenities')->firstOrFail();
        $rawRestCat = Category::create(['name' => 'Restaurant', 'sub_aspect_id' => $amenSub->id]);

        $polyRest = LocationPolygon::create();
        $polyRest->points()->createMany([
            ['latitude' => 33.5031, 'longitude' => 36.2592],
            ['latitude' => 33.5031, 'longitude' => 36.2594],
            ['latitude' => 33.5033, 'longitude' => 36.2594],
            ['latitude' => 33.5033, 'longitude' => 36.2592],
        ]);
        $locRest = $polyRest->locatable()->create([
            'name' => 'مطعم سافاري',
            'user_id' => $this->user->id,
            'aspect_id' => $urban->id,
            'sub_aspect_id' => $amenSub->id,
            'category_id' => $rawRestCat->id,
        ]);

        $this->seed(FixImportedLocationsGeometrySeeder::class);

        $locInstitute->refresh();
        $locBasicSchool->refresh();
        $locRest->refresh();

        $this->assertSame('Vocational training center', $locInstitute->category->name);
        $this->assertSame('Continuing education institution', $locBasicSchool->category->name);
        $this->assertSame('Commercial building', $locRest->category->name);

        // Verify 100% alignment with LocationController::getMarkerTypeById()
        $controller = new LocationController();
        foreach (Location::all() as $loc) {
            $expected = $controller->getMarkerTypeById($loc->category_id)[0] ?? null;
            $this->assertSame($loc->locatable_type, $expected);
        }

        // Verify updating these locations via API succeeds (no 422 category-type mismatch)
        Sanctum::actingAs($this->user);
        $this->putJson("/api/locations/{$locInstitute->id}", [
            'description' => 'تم التحديث بنجاح بعد مزامنة الفئة',
        ])->assertStatus(200);

        $this->putJson("/api/locations/{$locRest->id}", [
            'description' => 'مبنى تجاري مضلع متوافق',
        ])->assertStatus(200);

        // Verify idempotency on second run
        $snapshotBefore = [
            'aspects' => Aspect::count(),
            'categories' => Category::count(),
            'locations' => Location::count(),
            'poly_pts' => DB::table('location_polygon_points')->count(),
            'path_pts' => DB::table('location_path_points')->count(),
        ];

        $this->seed(FixImportedLocationsGeometrySeeder::class);

        $snapshotAfter = [
            'aspects' => Aspect::count(),
            'categories' => Category::count(),
            'locations' => Location::count(),
            'poly_pts' => DB::table('location_polygon_points')->count(),
            'path_pts' => DB::table('location_path_points')->count(),
        ];

        $this->assertSame($snapshotBefore, $snapshotAfter);
    }
}
