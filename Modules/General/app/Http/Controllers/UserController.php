<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\General\Http\Requests\UpdateUserRolesRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for(User::query())
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                AllowedFilter::partial('email', 'email'),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('name')
            ->with('roles:id,name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ]);

        return Inertia::render('General/Users/Index', [
            'users' => $users,
            'stats' => [
                'total' => User::count(),
                'owners' => User::role('owner')->count(),
            ],
            'can' => [
                'update' => $request->user()->can('tenant.users.update'),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('General/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
            'assigned_role_ids' => $user->roles->pluck('id'),
        ]);
    }

    public function update(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $requestedRoleIds = $request->input('roles', []);

        $canUpdate = DB::transaction(function () use ($requestedRoleIds, $user): bool {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $owner = Role::where('name', 'owner')->where('guard_name', 'web')->lockForUpdate()->first();
            $keepsOwner = $owner !== null && in_array($owner->id, $requestedRoleIds, true);

            if ($lockedUser->hasRole('owner') && ! $keepsOwner
                && ! User::role('owner')->where('id', '!=', $lockedUser->id)->exists()) {
                return false;
            }

            $lockedUser->syncRoles($requestedRoleIds);

            return true;
        }, attempts: 3);

        if (! $canUpdate) {
            return back()->withErrors(['roles' => 'No puedes quitar el rol owner al ultimo propietario.']);
        }

        return redirect()->route('tenant.users.index')->with('status', 'user-updated');
    }
}
