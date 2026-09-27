<?php

namespace App\Http\Middleware;

use App\Enums\TenantAccessBlockReason;
use App\Enums\TenantStatus;
use App\Models\Subscription;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the tenant workspace when the tenant was suspended by central staff
 * or its subscription lapsed (period ended, trial over, past due, cancelled
 * or expired). Instead of a bare 403 the user sees a "workspace disabled"
 * page explaining why, and can still log out. Tenants without any
 * subscription keep the baseline General module (module routes are already
 * gated by "tenant.module"). With the "allow-lapsed" option only a
 * suspension blocks: used by the public signatures website, which keeps
 * taking orders while the tenant renews.
 */
class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $option = null): Response
    {
        $tenant = tenant();

        abort_unless($tenant instanceof Tenant, 403, 'This tenant is not active.');

        if (! $tenant->operationalStatus()->equals(TenantStatus::Active())) {
            return $this->disabled($request, TenantAccessBlockReason::Suspended(), null);
        }

        if ($option === 'allow-lapsed') {
            return $next($request);
        }

        $subscription = tenancy()->central(fn (): ?Subscription => $tenant->subscription()->first());

        if ($subscription !== null && ! $subscription->grantsAccessAt()) {
            return $this->disabled($request, TenantAccessBlockReason::SubscriptionLapsed(), $subscription);
        }

        return $next($request);
    }

    private function disabled(Request $request, TenantAccessBlockReason $reason, ?Subscription $subscription): Response
    {
        return Inertia::render('General/AccessDisabled', [
            'reason' => $reason->value,
            'reason_label' => $reason->label,
            'subscription' => $subscription ? [
                'status_label' => $subscription->status->label,
                'trial_ends_at' => $subscription->trial_ends_at,
                'current_period_end' => $subscription->current_period_end,
            ] : null,
        ])->toResponse($request)->setStatusCode(Response::HTTP_FORBIDDEN);
    }
}
