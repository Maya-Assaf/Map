<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $positions = ['Head', 'CoHead', 'Senior leader', 'Junior leader', 'Volunteer'];
        $departments = ['IT & AI DEPARTMENT', 'RESEARCH DEPARTMENT', 'DESIGN DEPARTMENT', 'ADMIN DEPARTMENT', 'EDUCATION DEPARTMENT', 'MEDIA DEPARTMENT', 'FUNDRISING DEPARTMENT'];
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

         // إنشاء مستخدم خاص بقسم الـ ADMIN DEPARTMENT
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),  // كلمة السر الثابتة
            'position' => 'Head',
            'department' => 'ADMIN DEPARTMENT',
            'layer' => 'public health',
            'is_verified' => true
        ]);
    }
    }



