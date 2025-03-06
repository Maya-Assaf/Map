<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;
use App\Models\Permission;


class RolePermissionTableSeeder extends Seeder
{
   
    public function run()
    {
        $roles = Role::all();
        $permissions = Permission::all();

        // Assign all permissions to Head and CoHead
        $roles->whereIn('name', ['Head', 'CoHead'])->each(function ($role) use ($permissions) {
            $role->permissions()->sync($permissions->pluck('id'));
        });

        // Assign add, edit, and view permissions to Senior Leader
        $roles->where('name', 'Senior Leader')->each(function ($role) use ($permissions) {
            $role->permissions()->sync($permissions->whereIn('name', ['add-zone', 'edit-zone', 'view-zone','delete-zone'])->pluck('id'));
        });

        // Assign view permission to Junior Leader
        $roles->where('name', 'Junior Leader')->each(function ($role) use ($permissions) {
            $role->permissions()->sync($permissions->where('name', ['add-zone', 'edit-zone', 'view-zone','delete-zone'])->pluck('id'));
        });

        // Assign view permission to Volunteer
        $roles->where('name', 'Volunteer')->each(function ($role) use ($permissions) {
            $role->permissions()->sync($permissions->where('name', ['add-zone', 'edit-zone', 'view-zone','delete-zone'])->pluck('id'));
        });
    
    }
}
