<?php

use App\Http\Controllers\Admin\ReportPinController;
use App\Http\Controllers\Admin\UserPointsController;
use App\Http\Middleware\AdminBasicAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Panel de administración (HTTP Basic con ADMIN_USER / ADMIN_PASSWORD)
Route::middleware(AdminBasicAuth::class)->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserPointsController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/points', [UserPointsController::class, 'addPoints'])->name('users.points');
    Route::get('/reports/create', [ReportPinController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportPinController::class, 'store'])->name('reports.store');
});
