<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\InitializeTenancyOnTenantDomains;
use Illuminate\Support\Facades\Route;

// These routes aren't domain-bound (and "/" must not be re-registered in
// tenant routes, see routes/tenant.php): they answer on central and tenant
// domains alike, initializing tenancy only on tenant domains.
Route::middleware(InitializeTenancyOnTenantDomains::class)->group(function () {
    // Platform welcome page on central domains, tenant public website on tenant domains.
    Route::get('/', HomeController::class)->name('home');

    // Per-domain crawler files (there's no static public/robots.txt).
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
});
