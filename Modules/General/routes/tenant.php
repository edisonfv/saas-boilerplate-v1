<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\General\Http\Controllers\DashboardController;
use Modules\General\Http\Controllers\RoleController;
use Modules\General\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| General Module Routes (Tenant)
|--------------------------------------------------------------------------
|
| Login, dashboard, user management and role management — the baseline
| every tenant has regardless of plan. Registered by
| Modules/General/app/Providers/RouteServiceProvider.php, already wired
| with the tenancy middleware (InitializeTenancyByDomain,
| PreventAccessFromCentralDomains) by the customized nwidart stub.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('tenant.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('tenant.logout');

    Route::middleware('tenant.active')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');

        Route::middleware('permission:tenant.users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('tenant.users.index');
        });

        Route::middleware('permission:tenant.users.update')->group(function () {
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('tenant.users.edit');
            Route::patch('/users/{user}', [UserController::class, 'update'])->name('tenant.users.update');
        });

        Route::middleware('permission:tenant.roles.create')->group(function () {
            Route::get('/roles/create', [RoleController::class, 'create'])->name('tenant.roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('tenant.roles.store');
        });

        Route::middleware('permission:tenant.roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('tenant.roles.index');
        });

        Route::middleware('permission:tenant.roles.update')->group(function () {
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('tenant.roles.edit');
            Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('tenant.roles.update');
        });

        Route::middleware('permission:tenant.roles.delete')->group(function () {
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('tenant.roles.destroy');
        });
    });
});
