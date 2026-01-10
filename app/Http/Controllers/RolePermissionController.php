<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\PermissionGroup;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function getRoles()
    {
        $roles = Role::where('guard_name', 'api')->get();
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles')->where(function ($query) {
                    return $query->where('guard_name', 'api');
                }),
            ],
        ]);

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::create([
            'name' => strtolower($validated['name']),
            'guard_name' => 'api'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);

        if (strtolower($role->name) === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'The admin role cannot be deleted.',
            ], 403);
        }

        $role->delete();
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles')->where(function ($query) {
                    return $query->where('guard_name', 'api');
                })->ignore($role->id),
            ],
        ]);

        $newName = strtolower($request->name);

        if (strtolower($role->name) === 'admin' && $newName !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'The admin role name cannot be changed.',
            ], 403);
        }

        $role->name = $newName;
        $role->save();

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role,
        ]);
    }

    public function getPermissions()
    {
        $permissions = Permission::with('group')->where('guard_name', 'api')->get();
        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    public function createPermission(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('permissions')->where(function ($query) {
                    return $query->where('guard_name', 'api');
                }),
            ],
            'permission_group_id' => 'required|exists:permission_groups,id'
        ]);

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'api',
            'permission_group_id' => $validated['permission_group_id']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission
        ], 201);
    }

    public function getPermissionGroups()
    {
        $groups = PermissionGroup::all();
        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    public function createPermissionGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permission_groups,name|max:255',
        ]);

        $group = PermissionGroup::create([
            'name' => $validated['name']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission Group created successfully',
            'data' => $group
        ], 201);
    }

    public function updatePermissionGroup(Request $request, $id)
    {
        $group = PermissionGroup::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permission_groups,name,' . $group->id,
        ]);

        $group->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Permission Group updated successfully',
            'data' => $group
        ]);
    }

    public function deletePermissionGroup($id)
    {
        $group = PermissionGroup::findOrFail($id);

        if ($group->permissions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete group that has permissions assigned to it.'
            ], 422);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission Group deleted successfully'
        ]);
    }

    public function deletePermission($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully'
        ]);
    }

    public function updatePermission(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('permissions')->ignore($permission->id)->where(function ($query) {
                    return $query->where('guard_name', 'api');
                }),
            ],
            'permission_group_id' => 'required|exists:permission_groups,id'
        ]);

        $permission->name = $validated['name'];
        $permission->permission_group_id = $validated['permission_group_id'];
        $permission->save();

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission->load('group')
        ]);
    }

    /**
     * Get permissions for a specific role
     */
    public function getRolePermissions($roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = $role->permissions;

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }
    public function syncPermissionsToRole(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name', // Validate each permission exists
        ]);

        if (strtolower($role->name) === 'admin') {
             // Optional: Decide if admin should be editable. 
             // Usually admin has all permissions via Gate::before, 
             // but explicit assignment doesn't hurt.
        }

        $role->syncPermissions($request->permissions);
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned successfully',
            'data' => $role->load('permissions')
        ]);
    }
}
