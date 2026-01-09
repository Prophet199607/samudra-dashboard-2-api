<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:users,name',
            'password' => 'required|string|min:6',
            'location' => 'required|string',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'password' => bcrypt($validated['password']),
            'location' => $validated['location'],
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load('roles')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:users,name,' . $user->id,
            'location' => 'required|string',
            'role' => 'required|string|exists:roles,name',
            'password' => 'nullable|string|min:6'
        ]);

        $user->name = $validated['name'];
        $user->location = $validated['location'];
        
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load('roles')
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting self or specific restricted admins if needed
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete yourself.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
