<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\Signatures\SignatureSalesReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, SignatureSalesReport $report): Response
    {
        return Inertia::render('Central/Dashboard', [
            'stats' => [
                'plans' => Plan::count(),
                'modules' => Module::count(),
                'tenants' => Tenant::count(),
                'centralRoles' => Role::where('guard_name', 'central')->count(),
            ],
            // This month of the signature resale business, for whoever may see it.
            'signatures' => $request->user()->can('central.signature-sales.view') ? [
                'month' => $report->summary(now()->startOfMonth(), now()->endOfDay()),
                'alerts' => $report->alerts()->whereIn('tone', ['red', 'amber'])->count(),
            ] : null,
        ]);
    }
}
