<?php

namespace Database\Seeders;

use App\Models\Aspect;
use App\Models\SubAspect;
use App\Models\Category;
use Illuminate\Database\Seeder;

class AspectSubAspectCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Resource Mangement - > Energy Resources -> *Gas Station*
        $aspect1 = Aspect::whereIn('name', ['Resource Mangement', 'Resources Management', 'Resource Management'])->first()
            ?? Aspect::create(['name' => 'Resources Management']);

        $subAspect1 = SubAspect::where('aspect_id', $aspect1->id)
            ->whereIn('name', ['Energy Resources', 'Energy Resource'])
            ->first()
            ?? SubAspect::create(['name' => 'Energy Resources', 'aspect_id' => $aspect1->id]);

        Category::firstOrCreate([
            'name' => 'Gas Station',
            'sub_aspect_id' => $subAspect1->id,
        ]);

        // 2. Urban Planning -> Urban Goverance -> *Major ongoing project*
        $aspect2 = Aspect::whereIn('name', ['Urban Planning'])->first()
            ?? Aspect::create(['name' => 'Urban Planning']);

        $subAspect2 = SubAspect::where('aspect_id', $aspect2->id)
            ->whereIn('name', ['Urban Goverance', 'Urban Governance', 'Urban Governance Systems'])
            ->first()
            ?? SubAspect::create(['name' => 'Urban Governance Systems', 'aspect_id' => $aspect2->id]);

        Category::firstOrCreate([
            'name' => 'Major ongoing project',
            'sub_aspect_id' => $subAspect2->id,
        ]);

        // 3. Building Code -> Structural Integrity - > *Building Physical Structure*
        $aspect3 = Aspect::whereIn('name', ['Building Code', 'Building Code & Policy'])->first()
            ?? Aspect::create(['name' => 'Building Code & Policy']);

        $subAspect3 = SubAspect::where('aspect_id', $aspect3->id)
            ->whereIn('name', ['Structural Integrity'])
            ->first()
            ?? SubAspect::create(['name' => 'Structural Integrity', 'aspect_id' => $aspect3->id]);

        Category::firstOrCreate([
            'name' => 'Building Physical Structure',
            'sub_aspect_id' => $subAspect3->id,
        ]);

        // 4. Urban Planning -> Amenities - > *Public WC*
        $aspect4 = Aspect::whereIn('name', ['Urban Planning'])->first()
            ?? Aspect::create(['name' => 'Urban Planning']);

        $subAspect4 = SubAspect::where('aspect_id', $aspect4->id)
            ->whereIn('name', ['Amenities'])
            ->first()
            ?? SubAspect::create(['name' => 'Amenities', 'aspect_id' => $aspect4->id]);

        Category::firstOrCreate([
            'name' => 'Public WC',
            'sub_aspect_id' => $subAspect4->id,
        ]);

        // 5. Urban Planning -> Land Use - > *Mixed Use Zone*
        $aspect5 = Aspect::whereIn('name', ['Urban Planning'])->first()
            ?? Aspect::create(['name' => 'Urban Planning']);

        $subAspect5 = SubAspect::where('aspect_id', $aspect5->id)
            ->whereIn('name', ['Land Use'])
            ->first()
            ?? SubAspect::create(['name' => 'Land Use', 'aspect_id' => $aspect5->id]);

        Category::firstOrCreate([
            'name' => 'Mixed Use Zone',
            'sub_aspect_id' => $subAspect5->id,
        ]);

        // 6. Urban Planning -> Amenities - > *Bakery*
        $aspect6 = Aspect::whereIn('name', ['Urban Planning'])->first()
            ?? Aspect::create(['name' => 'Urban Planning']);

        $subAspect6 = SubAspect::where('aspect_id', $aspect6->id)
            ->whereIn('name', ['Amenities'])
            ->first()
            ?? SubAspect::create(['name' => 'Amenities', 'aspect_id' => $aspect6->id]);

        Category::firstOrCreate([
            'name' => 'Bakery',
            'sub_aspect_id' => $subAspect6->id,
        ]);
    }
}
