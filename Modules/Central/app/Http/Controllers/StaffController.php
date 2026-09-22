<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\UpdateStaffRolesRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $staff = QueryBuilder::for(CentralUser::query())
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                AllowedFilter::partial('email', 'email'),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('name')
            ->with('roles:id,name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (CentralUser $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'email_verified_at' => $user->email_verified_at,
            ]);

        return Inertia::render('Central/Staff/Index', [
            'staff' => $staff,
            'stats' => [
                'total' => CentralUser::count(),
                'superAdmins' => CentralUser::role('super-admin')->count(),
            ],
            'can' => [
                'create' => $request->user()->can('central.staff.create'),
                'update' => $request->user()->can('central.staff.update'),
            ],
        ]);
    }

    public function edit(CentralUser $user): Response
    {
        return Inertia::render('Central/Staff/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => Role::where('guard_name', 'central')->orderBy('name')->get(['id', 'name']),
            'assigned_role_ids' => $user->roles->pluck('id'),
        ]);
    }

    public function update(UpdateStaffRolesRequest $request, CentralUser $user): RedirectResponse
    {
        $requestedRoleIds = $request->input('roles', []);

        $canUpdate = DB::transaction(function () use ($requestedRoleIds, $user): bool {
            $lockedUser = CentralUser::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'central')->lockForUpdate()->first();
            $keepsSuperAdmin = $superAdmin !== null && in_array($superAdmin->id, $requestedRoleIds, true);

            if ($lockedUser->hasRole('super-admin') && ! $keepsSuperAdmin
                && ! CentralUser::role('super-admin')->where('id', '!=', $lockedUser->id)->exists()) {
                return false;
            }

            $lockedUser->syncRoles($requestedRoleIds);

            return true;
        }, attempts: 3);

        if (! $canUpdate) {
            return back()->withErrors(['roles' => 'No puedes quitar el rol super-admin al ultimo administrador.']);
        }

        return redirect()->route('central.staff.index')->with('status', 'staff-updated');
    }
}
