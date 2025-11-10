<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/cart/{session_cart_id}', [CartController::class, 'show']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::patch('/cart/{session_cart_id}/item/{item_id}', [CartController::class, 'update']);
    Route::delete('/cart/{session_cart_id}/item/{item_id}', [CartController::class, 'destroy']);

    Route::middleware('jwt')->group(function () {
        Route::patch('/cart/update-user', [CartController::class, 'updateUser']);
        Route::patch('/cart/shipping', [CartController::class, 'updateShipping']);
        Route::get('/auth-check', [ProductController::class, 'authCheck']);

        Route::get('/user', [AuthController::class, 'loginUserDetails']);

        //order
        Route::post('/order', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });
});
