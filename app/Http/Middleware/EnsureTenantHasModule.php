<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantEntitlements;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasModule
{
    public function __construct(private TenantEntitlements $entitlements) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleSlug, ?string $option = null): Response
    {
        $tenant = tenant();

        // "allow-lapsed": public surfaces that keep working while the tenant
        // renews (see TenantEntitlements::contractedModules()).
        $modules = $tenant instanceof Tenant
            ? ($option === 'allow-lapsed' ? $this->entitlements->contractedModules($tenant) : $this->entitlements->activeModules($tenant))
            : collect();

        abort_unless(
            $modules->contains($moduleSlug),
            403,
            "This tenant does not have the [{$moduleSlug}] module enabled."
        );

        return $next($request);
    }
}
