<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Cualquier rol autenticado puede consultar categorías (formulario de solicitudes).
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/{solicitud}', [RequestController::class, 'show']);
    Route::patch('/requests/{solicitud}/status', [RequestController::class, 'changeStatus']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
        Route::patch('/categories/{category}/toggle-active', [CategoryController::class, 'toggleActive']);
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/officers', [UserController::class, 'officers']);
        Route::post('/requests/{solicitud}/assignments', [RequestController::class, 'assign']);
        Route::get('/reports/summary', [ReportController::class, 'summary']);
    });

    Route::middleware('role:funcionario')->group(function () {
        Route::post('/requests/{solicitud}/observations', [RequestController::class, 'addObservation']);
    });
});
