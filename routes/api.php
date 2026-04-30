<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskGroupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
Route::post('/webhook/line', [LineController::class, 'webhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/profile', [UserController::class, 'profile']);
    Route::put('/auth/profile', [UserController::class, 'updateProfile']);

    // Me (alias)
    Route::get('/me', [UserController::class, 'profile']);

    // Task
    Route::prefix('task')->group(function () {
        Route::get('/today', [TaskController::class, 'getToday']);
        Route::get('/', [TaskController::class, 'get']);
        Route::post('/', [TaskController::class, 'create']);
        Route::get('/{id}', [TaskController::class, 'find']);
        Route::patch('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'delete']);
        Route::patch('/approve/{id}', [TaskController::class, 'approve']);
        Route::patch('/reject/{id}', [TaskController::class, 'reject']);
        Route::patch('/cancel/{id}', [TaskController::class, 'cancel']);
        Route::patch('/on-hold/{id}', [TaskController::class, 'onHold']);
        Route::patch('/complete/{id}', [TaskController::class, 'complete']);
    });

    // Admin / Personnel
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'get']);
        Route::get('/{id}', [AdminController::class, 'find']);
    });

    // Task Groups (legacy)
    Route::apiResource('groups', TaskGroupController::class);

    // Calendar
    Route::get('/calendar/events', [CalendarController::class, 'events']);
    Route::post('/calendar/sync', [CalendarController::class, 'syncTask']);
    Route::post('/calendar/sync-all', [CalendarController::class, 'syncAll']);
    Route::get('/calendar/shares', [CalendarController::class, 'listShares']);
    Route::post('/calendar/share', [CalendarController::class, 'shareCalendar']);
    Route::delete('/calendar/share', [CalendarController::class, 'unshareCalendar']);

    // LINE
    Route::get('/line/connect', [LineController::class, 'connect']);
    Route::post('/line/disconnect', [LineController::class, 'disconnect']);
});
