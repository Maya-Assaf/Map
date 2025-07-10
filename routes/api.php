<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\ResetPasswordController;

// تسجيل و تسجيل الدخول
Route::post('/check-employee', [AuthController::class, 'checkEmployee']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/resend-code', [AuthController::class, 'resendCode']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetLink']);
Route::post('/reset-password' , [ResetPasswordController::class , 'resetPassword']);

// Admin-only routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/admin/import-datasheet', [AuthController::class, 'importDatasheet']);
        Route::get('/users', [AdminController::class, 'index']);      // عرض المستخدمين مع pagination + search
        Route::post('/users', [AdminController::class, 'store']);     // إنشاء مستخدم جديد
        Route::put('/users/{id}', [AdminController::class, 'update']); // تعديل مستخدم
        Route::delete('/users/{id}', [AdminController::class, 'destroy']); // حذف مستخدم
        Route::get('/locations/exportcsv', [LocationController::class, 'exportCsv']); //تحميل ال markers كملف csv
    });

// المسارات المحمية للمستخدمين المسجلين فقط
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile/edit/{id}', [UserController::class, 'updateProfile']);

    Route::get('/user/profile', [UserController::class, 'getProfile']);




    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::get('/locations/filter', [LocationController::class, 'getLocationsByCategories']);
    
    Route::get('/locations/search', [LocationController::class, 'search']);
    Route::get('/locations/statistics', [LocationController::class, 'statistics']);
    Route::get('/locations/{id}', [LocationController::class, 'show']);
    Route::put('/locations/{id}', [LocationController::class, 'update']);
    Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
    Route::post('/locations/{id}/upload-files', [LocationController::class, 'uploadFiles']);
    Route::delete('/locations/{id}/delete-image/{imageId}', [LocationController::class, 'deleteImage']);
    Route::delete('/locations/{id}/delete-reference/{referenceId}', [LocationController::class, 'deleteReference']);
    Route::get('/aspects' , [LocationController::class , 'getAspects']);
    Route::get('/sub-aspects/{aspectId}', [LocationController::class, 'getSubAspects']);
    Route::get('/categories/{subAspectId}', [LocationController::class, 'getCategories']);
    
    Route::get('/zones' , [ZoneController::class , 'index']);
    Route::post('/zones' , [ZoneController::class,'store']);
    Route::get('/zone/{id}', [ZoneController::class,'show']);
    Route::put('/zone/{id}' , [ZoneController::class,'update']);
    Route::delete('/zone/{id}', [ZoneController::class,'destroy']);
    Route::get('/zones/filter' , [ZoneController::class,'getZonesByLayers']);
    Route::post('/updatePosition/{id}' , [UserController::class,'updatePosition']);
    Route::post('/updatePregisterUserPosition/{id}' , [AdminController::class,'updatePregisterUserPosition']); // by Akram







    // Route::prefix('roles')->group(function () {
    // Route::post('/{role}/permissions', [RolePermissionController::class, 'assignPermissions']);
    // Route::delete('/{role}/permissions', [RolePermissionController::class, 'revokePermissions']);
});

    

    // Route::middleware('layer.access:public health')->group(function () {
    //     Route::apiResource('zones', ExampleController::class);
    // });
    // Route::middleware('layer.access:data collection and analysis')->group(function () {
    //     Route::apiResource('zones', ExampleController::class);
    // });
    // Route::middleware('layer.access:economic factor')->group(function () {
    //     Route::apiResource('zones', ExampleController::class);
    // });

// });



