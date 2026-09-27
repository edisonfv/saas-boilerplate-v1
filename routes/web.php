<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// "/" isn't domain-bound (and must not be re-registered in tenant routes,
// see routes/tenant.php), so it serves both: the platform's welcome page on
// central domains, and a redirect to the tenant's own login on tenant
// subdomains (the central welcome links to the central console, which
// tenant domains don't serve).
Route::get('/', function (Request $request) {
    if (! in_array($request->getHost(), config('tenancy.central_domains', []), true)) {
        return redirect('/login');
    }

    return Inertia::render('Welcome');
})->name('home');
