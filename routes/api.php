<?php

use App\Http\Controllers\API\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('reset_pincode', [AuthController::class, 'resetPincode']);
});

// Account group
Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'account'
], function ($router) {
    // Get Profile
    Route::get('profile', [AccountController::class, 'getProfile']);
    // Update Profile
    Route::post('profile', [AccountController::class, 'updateProfile']);
    // Update FCM Token
    Route::post('update-fcm-token', [AccountController::class, 'updateFCMToken']);
    // Delete Account
    Route::post('delete-account', [AccountController::class, 'deleteAccount']);
});


// Order group
Route::group([
    'prefix' => 'order'
], function ($router) {
    // Get Menu Categories
    Route::get('categories', [OrderController::class, 'getMenuCategories']);

    // Get Menu Category Items
    Route::get('categories/{categoryId}/items', [OrderController::class, 'getMenuCategoryItems']);

    // Create Order
    Route::post('create', [OrderController::class, 'createOrder']);

    // Get Orders
    Route::get('all', [OrderController::class, 'getOrders']);

    // Get Order By Id
    Route::get('{orderId}', [OrderController::class, 'getOrderById']);

    // Update Order
    Route::put('{orderId}', [OrderController::class, 'updateOrder']);

    // Delete Order
    Route::delete('{orderId}', [OrderController::class, 'deleteOrder']);

    // Get Pending Orders
    Route::get('pending', [OrderController::class, 'getPendingOrders']);
});
