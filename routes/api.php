<?php

use App\Http\Controllers\Api\V1\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});

// Route::prefix('v1')->middleware([CorsMiddleware::class])->group(function () {
//     Route::apiResource('categories', CategoryController::class);
// });
