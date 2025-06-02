<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\User;
use App\Models\Aspect;
use App\Models\SubAspect;
use App\Models\Category;
use App\Models\LocationImage;

class LocationDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user6@example.com')->first();

        $aspect = Aspect::firstOrCreate(['id' => 1], ['name' => 'Culture & Heritage']);
        $subAspect = SubAspect::firstOrCreate(['id' => 1], [
            'name' => 'Cultural Hub',
            'aspect_id' => $aspect->id
        ]);
        $category = Category::firstOrCreate(['id' => 1], [
            'name' => 'Museums',
            'sub_aspect_id' => $subAspect->id
        ]);

        // The National Museum of Damascus
        $location1 = Location::create([
            'name' => 'The National Museum of Damascus',
            'user_id' => $user->id,
            'aspect_id' => $aspect->id,
            'sub_aspect_id' => $subAspect->id,
            'category_id' => $category->id,
            'description' => 'متحف في دمشق',
            'latitude' => '33.50000000',
            'longitude' => '36.50000000',
        ]);

        foreach ([
            'imgs/the-national-museum-of.jpg',
            'imgs/courtyard.jpg'
        ] as $path) {
            LocationImage::create([
                'location_id' => $location1->id,
                'image_path' => $path,
            ]);
        }

        // جبل قاسيون
        $aspect2 = Aspect::firstOrCreate(['name' => 'Natural Environment']);
        $subAspect2 = SubAspect::firstOrCreate([
            'name' => 'Mountain',
            'aspect_id' => $aspect2->id
        ]);
        $category2 = Category::firstOrCreate([
            'name' => 'Touristic Spots',
            'sub_aspect_id' => $subAspect2->id
        ]);

        $location2 = Location::create([
            'name' => 'جبل قاسيون',
            'user_id' => $user->id,
            'aspect_id' => $aspect2->id,
            'sub_aspect_id' => $subAspect2->id,
            'category_id' => $category2->id,
            'description' => 'جبل يطل على مدينة دمشق ويُعد من أبرز معالمها الطبيعية.',
            'latitude' => '33.54000000',
            'longitude' => '36.29000000',
        ]);

         foreach ([
            'imgs/the-view.jpg',
            'imgs/damascus-by-night.jpg'
        ] as $path2) {

            LocationImage::create([
                'location_id' => $location2->id,
                'image_path' => $path2,
        ]);
    }
    
        // مشفى ابن النفيس
        $aspect3 = Aspect::firstOrCreate(['name' => 'Health & Services']);
        $subAspect3 = SubAspect::firstOrCreate([
            'name' => 'Medical Facility',
            'aspect_id' => $aspect3->id
        ]);
        $category3 = Category::firstOrCreate([
            'name' => 'Hospitals',
            'sub_aspect_id' => $subAspect3->id
        ]);

        $location3 = Location::create([
            'name' => 'مشفى ابن النفيس',
            'user_id' => $user->id,
            'aspect_id' => $aspect3->id,
            'sub_aspect_id' => $subAspect3->id,
            'category_id' => $category3->id,
            'description' => 'مشفى حكومي يقدم خدمات طبية متقدمة في دمشق.',
            'latitude' => '33.513806',
            'longitude' => '36.276527',
        ]);

        LocationImage::create([
            'location_id' => $location3->id,
            'image_path' => 'imgs/ibn_alnafees_hospital.jpg',
        ]);
    }
}
