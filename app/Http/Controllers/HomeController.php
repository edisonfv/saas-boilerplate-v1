<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Controllers\StorefrontController;

/**
 * "/" of every domain. The route can't be split per domain (tenant routes
 * must not register "/", see routes/tenant.php), so it dispatches here:
 * the platform welcome page on central domains, the tenant's public
 * signatures website when it has that module, or its login otherwise.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, TenantEntitlements $entitlements): Response|RedirectResponse
    {
        $tenant = tenant();

        // Decide by host: a long-running process may still have the previous
        // request's tenant initialized.
        if (in_array($request->getHost(), config('tenancy.central_domains', []), true) || ! $tenant instanceof Tenant) {
            return Inertia::render('Welcome');
        }

        if ($entitlements->activeModules($tenant)->contains(config('signatures.module_slug', 'signatures'))) {
            return app()->call([app(StorefrontController::class), 'show']);
        }

        return redirect('/login');
    }
}
