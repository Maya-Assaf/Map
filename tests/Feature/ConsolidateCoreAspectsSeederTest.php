<?php

namespace Tests\Feature;

use App\Models\Aspect;
use App\Models\Category;
use App\Models\Location;
use App\Models\LocationPoint;
use App\Models\SubAspect;
use App\Models\User;
use Database\Seeders\ConsolidateCoreAspectsSeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidateCoreAspectsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_consolidates_extra_aspects_into_10_core_aspects_without_losing_locations(): void
    {
        $user = User::create([
            'name' => 'Seeder Tester',
            'email' => 'seeder@example.com',
            'password' => bcrypt('password123'),
            'position' => 'Head',
            'department' => 'ADMIN DEPARTMENT',
            'layer' => 'public health',
            'is_verified' => true,
        ]);

        // Seed the 10 Core Aspects
        $this->seed(LocationSeeder::class);
        $this->assertSame(10, Aspect::count());

        // Create extra non-core aspects mimicking JSON imports (Other, School, Housing, River, Culture & Heritage)
        $otherAspect = Aspect::create(['name' => 'Other']);
        $miscSub = SubAspect::create(['name' => 'Misc', 'aspect_id' => $otherAspect->id]);
        $otherSchoolCat = Category::create(['name' => 'School', 'sub_aspect_id' => $miscSub->id]);
        $otherResCat = Category::create(['name' => 'Landuse Residential', 'sub_aspect_id' => $miscSub->id]);

        $housingAspect = Aspect::create(['name' => 'Housing']);
        $housingSub = SubAspect::create(['name' => 'Housing And Building', 'aspect_id' => $housingAspect->id]);
        $buildingWayCat = Category::create(['name' => 'Building Way', 'sub_aspect_id' => $housingSub->id]);

        $riverAspect = Aspect::create(['name' => 'River']);
        $riverSub = SubAspect::create(['name' => 'River', 'aspect_id' => $riverAspect->id]);
        $riverCat = Category::create(['name' => 'Waterway River', 'sub_aspect_id' => $riverSub->id]);

        $this->assertSame(13, Aspect::count());

        // Create locations attached to these extra aspects
        $loc1 = $this->makePointLocation($user->id, 'Other School 1', $otherAspect->id, $miscSub->id, $otherSchoolCat->id);
        $loc2 = $this->makePointLocation($user->id, 'Other Residential 1', $otherAspect->id, $miscSub->id, $otherResCat->id);
        $loc3 = $this->makePointLocation($user->id, 'Housing Block 1', $housingAspect->id, $housingSub->id, $buildingWayCat->id);
        $loc4 = $this->makePointLocation($user->id, 'Barada River', $riverAspect->id, $riverSub->id, $riverCat->id);

        $this->assertSame(4, Location::count());

        // Run the consolidation seeder
        $this->seed(ConsolidateCoreAspectsSeeder::class);

        // Exactly 10 Core Aspects must remain, and all 4 locations must still exist with valid foreign keys
        $this->assertSame(10, Aspect::count());
        $this->assertSame(4, Location::count());
        $this->assertSame(0, Location::whereNull('aspect_id')->count());
        $this->assertSame(0, Location::whereNull('sub_aspect_id')->count());
        $this->assertSame(0, Location::whereNull('category_id')->count());

        // Verify exact mappings
        $loc1->refresh();
        $this->assertSame('Social', $loc1->aspect->name);
        $this->assertSame('Education System', $loc1->subAspect->name);

        $loc2->refresh();
        $this->assertSame('Urban Planning', $loc2->aspect->name);
        $this->assertSame('Land Use', $loc2->subAspect->name);

        $loc3->refresh();
        $this->assertSame('Urban Planning', $loc3->aspect->name);
        $this->assertSame('Housing & Buildings', $loc3->subAspect->name);

        $loc4->refresh();
        $this->assertSame('Resources Management', $loc4->aspect->name);
        $this->assertSame('Water Resources', $loc4->subAspect->name);
    }

    private function makePointLocation(int $userId, string $name, int $aspectId, int $subAspectId, int $categoryId): Location
    {
        $pt = LocationPoint::create([
            'latitude' => 33.5138,
            'longitude' => 36.2765,
        ]);

        return $pt->locatable()->create([
            'name' => $name,
            'user_id' => $userId,
            'aspect_id' => $aspectId,
            'sub_aspect_id' => $subAspectId,
            'category_id' => $categoryId,
        ]);
    }
}
