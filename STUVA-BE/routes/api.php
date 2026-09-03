<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;

// Public Route
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Butuh Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route MDM & Location Tracking
    Route::post('/location/ping', [LocationController::class, 'store']);
    Route::get('/location/live', [LocationController::class, 'index']);

    // Route Permission Requests (Multi-Approval)
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']); // Siswa
    Route::post('/permissions/{id}/parent-approve', [PermissionController::class, 'parentApproval']); // Ortu
    Route::post('/permissions/{id}/teacher-approve', [PermissionController::class, 'teacherApproval']); // Guru
});


Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/students', [UserController::class, 'getStudents']);
    Route::apiResource('/users', UserController::class);
});