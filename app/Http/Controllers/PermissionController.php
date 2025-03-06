<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the permissions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $permissions = Permission::all();
        return response()->json($permissions);
    }

    /**
     * Store a newly created permission in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        $permission = Permission::create($request->all());
        return response()->json($permission, 201);
    }

    /**
     * Display the specified permission.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }

    /**
     * Update the specified permission in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|unique:permissions,name,' . $id,
        ]);

        $permission->update($request->all());
        return response()->json($permission);
    }

    /**
     * Remove the specified permission from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        return response()->json(null, 204);
    }
}