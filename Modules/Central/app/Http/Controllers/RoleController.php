<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\StoreRoleRequest;
use Modules\Central\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = QueryBuilder::for(Role::where('guard_name', 'central'))
            ->allowedFilters(AllowedFilter::partial('search', 'name'))
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->withCount(['permissions', 'users'])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'is_protected' => $role->name === 'super-admin',
            ]);

        return Inertia::render('Central/Roles/Index', [
            'roles' => $roles,
            'stats' => [
                'total' => Role::where('guard_name', 'central')->count(),
                'totalPermissions' => Permission::where('guard_name', 'central')->count(),
            ],
            'can' => [
                'create' => $request->user()->can('central.roles.create'),
                'update' => $request->user()->can('central.roles.update'),
                'delete' => $request->user()->can('central.roles.delete'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/Roles/Create', [
            'permissions' => $this->groupedPermissions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $role = Role::create(['name' => $request->string('name')->toString(), 'guard_name' => 'central']);
            $role->syncPermissions($request->input('permissions', []));
        });

        return redirect()->route('central.roles.index')->with('status', 'role-created');
    }

    public function edit(Role $role): Response
    {
        abort_if($role->guard_name !== 'central', 404);
        abort_if($role->name === 'super-admin', 403, 'El rol super-admin no puede modificarse.');

        return Inertia::render('Central/Roles/Edit', [
            'permissions' => $this->groupedPermissions(),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'assigned_permission_ids' => $role->permissions->pluck('id'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->guard_name !== 'central', 404);
        abort_if($role->name === 'super-admin', 403, 'El rol super-admin no puede modificarse.');

        DB::transaction(function () use ($request, $role): void {
            $role->update(['name' => $request->string('name')->toString()]);
            $role->syncPermissions($request->input('permissions', []));
        });

        return redirect()->route('central.roles.index')->with('status', 'role-updated');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->guard_name !== 'central', 404);
        abort_if($role->name === 'super-admin', 403, 'El rol super-admin no puede eliminarse.');

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'No puedes eliminar un rol asignado a usuarios.']);
        }

        $role->delete();

        return redirect()->route('central.roles.index')->with('status', 'role-deleted');
    }

    /**
     * @return array<int, array{group: string, items: array<int, array{id: string, name: string, label: string}>}>
     */
    private function groupedPermissions(): array
    {
        return Permission::where('guard_name', 'central')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[1] ?? $permission->name)
            ->map(fn ($permissions, string $group) => [
                'group' => $group,
                'items' => $permissions->map(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $permission->label ?? $permission->name,
                ])->values(),
            ])
            ->values()
            ->all();
    }
}
