<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\User;

class LocationSeeder extends Seeder
{
    public function run()
    {
        $categories = ['Hospital', 'School', 'Market', 'Park', 'Government Building'];
        $layers = ['public health', 'resources management', 'economic factor', 'urban planning', 'ecological factor', 'social factor', 'building code', 'Culture and heritage', 'technology and infrastructure', 'data collection and analysis'];

        for ($i = 1; $i <= 20; $i++) {
            Location::create([
                'name' => "Location $i",
                'category' => $categories[array_rand($categories)],
                'user_id' => User::inRandomOrder()->first()->id, // اختيار مستخدم عشوائي
                'latitude' => 33.5000 + (rand(0, 1000) / 10000), // نطاق عشوائي ضمن دمشق
                'longitude' => 36.3000 + (rand(0, 1000) / 10000), // نطاق عشوائي ضمن دمشق
            ]);
        }
    }
}
