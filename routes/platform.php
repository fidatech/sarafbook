<?php

use App\Http\Controllers\Platform\AuthController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\PlanController;
use App\Http\Controllers\Platform\TenantController;
use Illuminate\Support\Facades\Route;

/*
| The platform panel (prefix /platform, names platform.*). Served only on CENTRAL_DOMAINS.
*/

Route::get('lang/{locale}', [AuthController::class, 'locale'])->name('locale');

Route::middleware('guest:platform')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth:platform')->group(function () {
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::post('tenants/{tenant}/status', [TenantController::class, 'status'])->name('tenants.status');
    Route::post('tenants/{tenant}/payments', [TenantController::class, 'payment'])->name('tenants.payment');
    Route::post('tenants/{tenant}/domains', [TenantController::class, 'domain'])->name('tenants.domain');
    Route::post('tenants/{tenant}/migrate', [TenantController::class, 'migrate'])->name('tenants.migrate');
    Route::post('tenants/{tenant}/enter', [TenantController::class, 'enter'])->name('tenants.enter');

    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
    Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
    Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

    Route::get('audit', [DashboardController::class, 'audit'])->name('audit');
});
