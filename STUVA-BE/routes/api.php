<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\DashboardAdminController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\AdminAttendanceController;
use App\Http\Controllers\Api\TeacherDashboardController;
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
    Route::post('/permissions/{id}/teacher-approve', [PermissionController::class, 'teacherApproval']); // Guru
    Route::post('/permissions/{id}/parent-approve', [PermissionController::class, 'parentApproval']); // Ortu
    Route::get('/siswa/attendance-history', [StudentAttendanceController::class, 'index']);

    // Route Teacher Dashboard
    Route::get('/guru/dashboard', [TeacherDashboardController::class, 'index']);
    
    // Route Change Password
    Route::post('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/change-password', [AuthController::class, 'changePassword']);

    // Crud User (Admin)
    Route::get('/available-classes', [UserController::class, 'getAvailableClasses']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/stats', [UserController::class, 'getStats']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// Admin Routes (Butuh Token Sanctum)
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

    Route::get('/dashboard/overview', [DashboardAdminController::class, 'overview']);
    Route::get('/students', [UserController::class, 'getStudents']);

    // Route untuk mendapatkan statistik jumlah user berdasarkan role

    Route::get('/attendances', [AdminAttendanceController::class, 'index']);
    Route::post('/attendances', [AdminAttendanceController::class, 'store']);
    Route::get('/attendances/{id}', [AdminAttendanceController::class, 'show']);
    Route::post('/attendances/{id}', [AdminAttendanceController::class, 'update']);
    Route::delete('/attendances/{id}', [AdminAttendanceController::class, 'destroy']);
});

// Parent Routes (Butuh Token Sanctum)
Route::middleware(['auth:sanctum'])->prefix('ortu')->group(function () {
    Route::get('/attendance-history', [StudentAttendanceController::class, 'index']);
    Route::get('/child-status', [ParentController::class, 'getChildStatus']);
    Route::get('/permissions', [ParentController::class, 'getPendingPermissions']);
});
