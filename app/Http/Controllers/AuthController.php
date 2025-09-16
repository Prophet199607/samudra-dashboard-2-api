<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Location;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username'   => 'required|string',
            'password'   => 'required|string',
            'location'   => 'required|string',
        ]);

        $locationExists = Location::where('Loca', $credentials['location'])->exists();

        if (!$locationExists) {
            return response()->json(['error' => 'Invalid location'], 401);
        }

        $user = User::where('username', $credentials['username'])
                    ->where('location', $credentials['location'])
                    ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'user'         => $user->load('location')
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function authUser()
    {
        $user = User::with('location')->find(auth()->id());

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(['user' => $user]);
    }

    public function dashboard()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'message' => 'Welcome to your dashboard',
            'user'    => $user
        ]);
    }
}
