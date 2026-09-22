<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantEntitlements;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasFeature
{
    public function __construct(private TenantEntitlements $entitlements) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureSlug): Response
    {
        $tenant = tenant();

        abort_unless(
            $tenant instanceof Tenant && $this->entitlements->activeFeatures($tenant)->contains($featureSlug),
            403,
            "This tenant does not have the [{$featureSlug}] feature enabled."
        );

        return $next($request);
    }
}
