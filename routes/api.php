<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerOrdersController;
use App\Http\Controllers\DataRepositoryController;
use App\Http\Controllers\PreviousCollectionController;
use App\Http\Controllers\RolePermissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('dashboard', [AuthController::class, 'dashboard']);
    Route::get('auth/user', [AuthController::class, 'authUser']);

    // ===============================
    // ADMIN / USER MANAGEMENT
    // ===============================
    Route::middleware('permission:view users|edit users')->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::delete('{id}', [UserController::class, 'destroy']);
    });

    // ===============================
    // ROLES & PERMISSIONS
    // ===============================
    Route::prefix('roles')->middleware('permission:view users|edit users')->group(function () {
        Route::get('/', [RolePermissionController::class, 'getRoles']);
        Route::post('/', [RolePermissionController::class, 'createRole']);
        Route::put('{id}', [RolePermissionController::class, 'updateRole']);
        Route::delete('{id}', [RolePermissionController::class, 'deleteRole']);
        Route::get('{id}/permissions', [RolePermissionController::class, 'getRolePermissions']);
        Route::post('{id}/permissions', [RolePermissionController::class, 'syncPermissionsToRole']);
    });

    Route::prefix('permissions')->middleware('permission:view users|edit users')->group(function () {
        Route::get('/', [RolePermissionController::class, 'getPermissions']);
        Route::post('/', [RolePermissionController::class, 'createPermission']);
        Route::put('{id}', [RolePermissionController::class, 'updatePermission']);
        Route::delete('{id}', [RolePermissionController::class, 'deletePermission']);
    });

    // ===============================
    // DATA REPOSITORY
    // ===============================
    Route::middleware('permission:create order|sales order')->group(function () {
        Route::get('customers', [DataRepositoryController::class, 'getCustomers']);
        Route::get('customer-groups', [DataRepositoryController::class, 'getCustomerGroups']);
        Route::get('payment-types', [DataRepositoryController::class, 'getPaymentTypes']);
    });

    // ===============================
    // CUSTOMER ORDERS
    // ===============================
    Route::prefix('orders')->group(function () {
        Route::middleware('permission:create order|sales order')->group(function () {
            Route::get('generate-orn', [CustomerOrdersController::class, 'generateOrnNumber']);
            Route::get('dashboard-stats', [CustomerOrdersController::class, 'getDashboardStats']);
            Route::get('/', [CustomerOrdersController::class, 'getAllOrderDetails']);
            Route::get('{ornNumber}', [CustomerOrdersController::class, 'getOrder']);
        });

        Route::middleware('permission:create order')->group(function () {
            Route::post('new', [CustomerOrdersController::class, 'createOrder']);
        });

        Route::middleware('permission:create order|sales order|quotation|invoice|delivery')->group(function () {
             // General update route - controller handles specific step updates, 
             // but we allow users with step permissions to hit this.
            Route::put('{ornNumber}', [CustomerOrdersController::class, 'updateOrder']);
            Route::put('{ornNumber}/delay', [CustomerOrdersController::class, 'updateDelay']);
        });
    });

    // ===============================
    // PREVIOUS COLLECTIONS
    // ===============================
    Route::prefix('prv-collections')->group(function () {
        Route::middleware('permission:collection receipt')->group(function () {
            Route::get('generate-pc', [PreviousCollectionController::class, 'generatePCNumber']);
            Route::get('/', [PreviousCollectionController::class, 'getAllCollections']);
            Route::get('{pcNumber}', [PreviousCollectionController::class, 'getCollection']);
            Route::post('new', [PreviousCollectionController::class, 'createCollection']);
            Route::put('{pcNumber}', [PreviousCollectionController::class, 'updateCollection']);
        });
    });

    // ===============================
    // TRANSACTIONS
    // ===============================
    Route::prefix('transactions')->middleware('permission:approve order|sales order|quotation')->group(function () {
        Route::post('approved-orders', [CustomerOrdersController::class, 'getApprovedOrders']);
        Route::post('update-sales-order', [CustomerOrdersController::class, 'updateSalesOrder']);
        Route::post('update-quotation', [CustomerOrdersController::class, 'updateQuotation']);
    });
});
