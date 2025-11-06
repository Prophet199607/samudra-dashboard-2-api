<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerOrdersController;
use App\Http\Controllers\DataRepositoryController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', [AuthController::class, 'login']);

// Proxy for external locations API
Route::get('locations', [DataRepositoryController::class, 'getLocations']);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('dashboard', [AuthController::class, 'dashboard']);
    Route::get('auth/user', [AuthController::class, 'authUser']);

    // Data Repository
    Route::get('customers', [DataRepositoryController::class, 'getCustomers']);
    Route::get('customer-groups', [DataRepositoryController::class, 'getCustomerGroups']);
    Route::get('payment-types', [DataRepositoryController::class, 'getPaymentTypes']);

    // Customer Orders
    Route::prefix('orders')->group(function () {
        Route::get('generate-orn', [CustomerOrdersController::class, 'generateOrnNumber']);
        Route::get('dashboard-stats', [CustomerOrdersController::class, 'getDashboardStats']);
        Route::get('/', [CustomerOrdersController::class, 'getAllOrderDetails']);
        Route::get('{ornNumber}', [CustomerOrdersController::class, 'getOrder']);
        Route::post('/new', [CustomerOrdersController::class, 'createOrder']);
        Route::put('{ornNumber}', [CustomerOrdersController::class, 'updateOrder']);
    });
});
