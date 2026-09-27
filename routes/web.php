<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\InitializeTenancyOnTenantDomains;
use Illuminate\Support\Facades\Route;

// "/" isn't domain-bound (and must not be re-registered in tenant routes,
// see routes/tenant.php): HomeController serves the platform welcome page
// on central domains and the tenant's public website on tenant domains.
Route::get('/', HomeController::class)
    ->middleware(InitializeTenancyOnTenantDomains::class)
    ->name('home');
