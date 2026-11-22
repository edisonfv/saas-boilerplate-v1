<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counterpart of stancl's PreventAccessFromCentralDomains: central-only
 * routes (the platform console under /central) answer 404 on tenant
 * subdomains. Without it, e.g. acme.example.com/central/login would serve
 * the central login (no tenancy initialized) and look tenant users up in
 * the central database.
 */
class PreventAccessFromTenantDomains
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(in_array($request->getHost(), config('tenancy.central_domains', []), true), 404);

        return $next($request);
    }
}
