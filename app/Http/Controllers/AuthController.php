<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username'   => 'required|string',
            'password'   => 'required|string',
            'location'   => 'required|string',
        ]);

        // try {
        //     if (!$this->isLocationValid($credentials['location'])) {
        //         return response()->json(['error' => 'Invalid location selected'], 401);
        //     }
        // } catch (\Exception) {
        //     return response()->json(['error' => 'Could not verify location. Please try again later.'], 500);
        // }

        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password) || $user->location !== $credentials['location']) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'user'         => $user
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function authUser()
    {
        $user = User::find(auth()->id());

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

    private function isLocationValid(string $locationCode): bool
    {
        $externalApiBaseUrl = env('EXTERNAL_API_BASE_URL');
        $externalApiUser = env('EXTERNAL_API_USER');
        $externalApiPass = env('EXTERNAL_API_PASS');

        if (!$externalApiBaseUrl || !$externalApiUser || !$externalApiPass) {
            throw new \Exception('External API credentials for location validation are not configured.');
        }

        try {
            $response = Http::withBasicAuth($externalApiUser, $externalApiPass)
                ->get(rtrim($externalApiBaseUrl, '/') . '/api/Master/GetLocations');

            if ($response->serverError()) {
                throw new \Exception('External location service returned a server error. Status: ' . $response->status());
            }
            if ($response->clientError()) {
                throw new \Exception('External location service returned a client error (e.g., auth failed). Status: ' . $response->status());
            }

            $externalLocationsData = $response->json();

            if (!isset($externalLocationsData['locations']) || !is_array($externalLocationsData['locations'])) {
                throw new \Exception('Invalid response format from external locations API.');
            }

            return collect($externalLocationsData['locations'])->contains('Code', $locationCode);
        } catch (\Exception $e) {
            throw new \Exception('Exception during external locations API call: ' . $e->getMessage(), 0, $e);
        }
    }
}
