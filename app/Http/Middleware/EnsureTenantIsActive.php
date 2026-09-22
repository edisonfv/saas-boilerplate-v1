<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        abort_unless(
            $tenant instanceof Tenant && $tenant->operationalStatus()->equals(TenantStatus::Active()),
            403,
            'This tenant is not active.',
        );

        return $next($request);
    }
}
