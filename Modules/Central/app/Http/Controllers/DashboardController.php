<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Role;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Central/Dashboard', [
            'stats' => [
                'plans' => Plan::count(),
                'modules' => Module::count(),
                'tenants' => Tenant::count(),
                'centralRoles' => Role::where('guard_name', 'central')->count(),
            ],
        ]);
    }
}
