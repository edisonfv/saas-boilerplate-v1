<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantEntitlements;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Central and tenant requests authenticate against different guards
        // (see App\Models\CentralUser vs App\Models\User) — pick the one
        // that applies to the domain this request is on.
        $user = tenant() ? $request->user('web') : $request->user('central');

        $tenant = tenant();

        // Controllers flash a `status` key (e.g. "plan-created") the classic
        // way; forward it as Inertia flash data so the layouts can toast it
        // (see resources/js/lib/flash.ts) without it being stored in history.
        if ($request->session()->has('status')) {
            Inertia::flash('status', $request->session()->get('status'));
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'permissions' => $user?->getAllPermissions()->pluck('name') ?? [],
            ],
            // Lets the tenant navigation hide items of modules the tenant
            // hasn't contracted (their routes 403 via "tenant.module").
            'tenant' => $tenant instanceof Tenant ? [
                'id' => $tenant->getTenantKey(),
                'name' => $tenant->company_name ?? $tenant->getTenantKey(),
                'modules' => app(TenantEntitlements::class)->activeModules($tenant)->values(),
            ] : null,
            // Dates are rendered in the app timezone (support hours, SLAs,
            // bookings are all defined in it).
            'timezone' => config('app.timezone'),
        ];
    }
}
