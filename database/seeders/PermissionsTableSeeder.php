<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;


class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['name' => 'add-zone'],
            ['name' => 'edit-zone'],
            ['name' => 'delete-zone'],
            ['name' => 'view-zone'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}