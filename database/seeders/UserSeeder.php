<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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



