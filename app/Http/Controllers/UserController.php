<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Return all users
        // You might want to add 'roles' linkage if using spatie/laravel-permission
        $users = User::all();
        return response()->json($users);
    }
}
