<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;


class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'Head'],
            ['name' => 'CoHead'],
            ['name' => 'Senior Leader'],
            ['name' => 'Junior Leader'],
            ['name' => 'Volunteer'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}