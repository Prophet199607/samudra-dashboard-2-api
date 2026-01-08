<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerOrdersController;
use App\Http\Controllers\DataRepositoryController;
use App\Http\Controllers\PreviousCollectionController;


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
        Route::put('{ornNumber}/delay', [CustomerOrdersController::class, 'updateDelay']);
    });

    // Previous Collections
    Route::prefix('prv-collections')->group(function () {
        Route::get('generate-pc', [PreviousCollectionController::class, 'generatePCNumber']);
        Route::get('/', [PreviousCollectionController::class, 'getAllCollections']);
        Route::get('{pcNumber}', [PreviousCollectionController::class, 'getCollection']);
        Route::post('/new', [PreviousCollectionController::class, 'createCollection']);
        Route::put('{pcNumber}', [PreviousCollectionController::class, 'updateCollection']);
    });
});

// Transactions
Route::prefix('transactions')->group(function () {
    Route::post('approved-orders', [CustomerOrdersController::class, 'getApprovedOrders']);
    Route::post('update-sales-order', [CustomerOrdersController::class, 'updateSalesOrder']);
    Route::post('update-quotation', [CustomerOrdersController::class, 'updateQuotation']);
});