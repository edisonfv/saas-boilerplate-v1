<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TenantEntitlements;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(TenantEntitlements $entitlements): Response
    {
        $tenant = tenant();

        $subscription = tenancy()->central(function () use ($tenant) {
            $subscription = $tenant->subscription()->with('plan')->first();

            return $subscription ? [
                'plan_name' => $subscription->plan->name,
                'billing_period_label' => $subscription->billing_period->label,
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label,
                'trial_ends_at' => $subscription->trial_ends_at,
                'current_period_end' => $subscription->current_period_end,
            ] : null;
        });

        return Inertia::render('General/Dashboard', [
            'subscription' => $subscription,
            'entitlements' => $subscription ? [
                'modules' => $entitlements->activeModules($tenant)->values(),
                'features' => $entitlements->activeFeatures($tenant)->values(),
                'limits' => $entitlements->effectiveLimits($tenant),
            ] : null,
        ]);
    }
}
