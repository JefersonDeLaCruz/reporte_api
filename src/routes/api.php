<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\DocsController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\ReportStreamController;
use App\Http\Controllers\API\ReportVoteController;
use Illuminate\Support\Facades\Route;

// Docs (auto-documentación de endpoints)
Route::get('/docs', [DocsController::class, 'index']);

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'googleLogin']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/reports', [ReportController::class, 'index']);
Route::get('/reports/{report}', [ReportController::class, 'show']);
Route::get('/reports/stream/changes', [ReportStreamController::class, 'changes']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::post('/me/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::get('/me/reports', [ProfileController::class, 'reports']);
    Route::get('/me/votes', [ProfileController::class, 'votes']);
    Route::get('/users', [AuthController::class, 'getAllUser']);

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/reports', [ReportController::class, 'store']);
    Route::put('/reports/{report}', [ReportController::class, 'update']);
    Route::delete('/reports/{report}', [ReportController::class, 'destroy']);
    Route::patch('/reports/{report}/status', [ReportController::class, 'updateStatus']);

    Route::post('/reports/{report}/votes', [ReportVoteController::class, 'store']);
    Route::delete('/reports/{report}/votes/{type}', [ReportVoteController::class, 'destroy']);
});
