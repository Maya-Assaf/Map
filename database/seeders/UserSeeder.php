<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $positions = ['Head', 'CoHead', 'Senior leader', 'Junior leader', 'Volunteer'];
        $departments = ['IT&AI', 'Research', 'Design', 'Admin', 'Education', 'Media', 'Fundrising'];
        $layers = ['public health', 'resources management', 'economic factor', 'urban planning', 'ecological factor', 'social factor', 'building code', 'Culture and heritage', 'technology and infrastructure', 'data collection and analysis'];

        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => Hash::make('password123'),
                'position' => $positions[array_rand($positions)],
                'department' => $departments[array_rand($departments)],
                'layer' => $layers[array_rand($layers)],
            ]);
        }
    }
}
