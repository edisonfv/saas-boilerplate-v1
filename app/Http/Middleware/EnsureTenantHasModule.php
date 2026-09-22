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
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $tenant = tenant();

        abort_unless(
            $tenant instanceof Tenant && $this->entitlements->activeModules($tenant)->contains($moduleSlug),
            403,
            "This tenant does not have the [{$moduleSlug}] module enabled."
        );

        return $next($request);
    }
}
