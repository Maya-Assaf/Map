<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    /**
     * Assign permissions to a role.
     *
     * @param Request $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name', // Validate that the permissions exist
        ]);

        // Get permission IDs from the provided permission names
        $permissions = Permission::whereIn('name', $request->permissions)->pluck('id');

        // Sync permissions to the role
        $role->permissions()->syncWithoutDetaching($permissions);

        return response()->json([
            'message' => 'Permissions assigned successfully.',
            'role' => $role->load('permissions'), // Return the role with its permissions
        ]);
    }

    /**
     * Revoke permissions from a role.
     *
     * @param Request $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name', // Validate that the permissions exist
        ]);

        // Get permission IDs from the provided permission names
        $permissions = Permission::whereIn('name', $request->permissions)->pluck('id');

        // Detach permissions from the role
        $role->permissions()->detach($permissions);

        return response()->json([
            'message' => 'Permissions revoked successfully.',
            'role' => $role->load('permissions'), // Return the role with its permissions
        ]);
    }
}