<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * For the few routes shared by central and tenant domains (only "/"
 * today): initializes tenancy by domain when the request comes from a
 * tenant domain, and does nothing on central domains. stancl only ships
 * middleware that require one or the other.
 */
class InitializeTenancyOnTenantDomains
{
    public function __construct(private InitializeTenancyByDomain $initializeTenancy) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getHost(), config('tenancy.central_domains', []), true)) {
            return $next($request);
        }

        return $this->initializeTenancy->handle($request, $next);
    }
}
