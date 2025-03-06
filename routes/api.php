<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExampleController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayerController;
use App\Http\Controllers\RolePermissionController;

// تسجيل و تسجيل الدخول
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// المسارات المحمية للمستخدمين المسجلين فقط
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/edite', [AuthController::class, 'updateProfile']);
    Route::get('/user/profile', [AuthController::class, 'getProfile']);


    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::get('/locations/filter', [LocationController::class, 'getLocationsByLayers']);
    Route::get('/locations/export/csv', [LocationController::class, 'exportCsv']);
    Route::get('/locations/search', [LocationController::class, 'search']);
    Route::get('/locations/statistics', [LocationController::class, 'statistics']);
    Route::get('/locations/{id}', [LocationController::class, 'show']);
    Route::put('/locations/{id}', [LocationController::class, 'update']);
    Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
    Route::post('/locations/{id}/upload-files', [LocationController::class, 'uploadFiles']);
    Route::delete('/locations/{id}/delete-image/{imageId}', [LocationController::class, 'deleteImage']);
    Route::delete('/locations/{id}/delete-reference/{referenceId}', [LocationController::class, 'deleteReference']);
   
  
Route::prefix('roles')->group(function () {
    Route::post('/{role}/permissions', [RolePermissionController::class, 'assignPermissions']);
    Route::delete('/{role}/permissions', [RolePermissionController::class, 'revokePermissions']);
});



});

Route::middleware(['auth:sanctum'])->group(function () {
   
    Route::middleware('layer.access:public health')->group(function () {
        Route::apiResource('zones', ExampleController::class);
    });
    Route::middleware('layer.access:data collection and analysis')->group(function () {
        Route::apiResource('zones', ExampleController::class);
    });
    Route::middleware('layer.access:economic factor')->group(function () {
        Route::apiResource('zones', ExampleController::class);
    }); 
      
});

