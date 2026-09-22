<?php

declare(strict_types=1);

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| In this project, tenant routes live inside each module's own
| routes/tenant.php (registered by that module's RouteServiceProvider —
| see stubs/nwidart-stubs/route-provider.stub). This root-level file is for
| app-wide tenant routes that don't belong to any one module. Login,
| dashboard, users and roles live in Modules/General — see
| Modules/General/routes/tenant.php.
|
| IMPORTANT: routes here are registered late, via
| TenancyServiceProvider::mapRoutes() (an app()->booted() callback), which
| runs after routes/web.php. A route here can silently overwrite a
| same-URI route registered elsewhere — this already happened once with
| "/" clobbering the central "home" route. Never reuse "/" here; always
| use a distinct path.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    //
});
