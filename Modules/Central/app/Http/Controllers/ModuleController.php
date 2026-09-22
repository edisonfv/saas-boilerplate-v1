<?php

namespace Modules\Central\Http\Controllers;

use App\Enums\Action;
use App\Enums\BillingPeriod;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModulePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\StoreModuleRequest;
use Modules\Central\Http\Requests\UpdateModuleRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ModuleController extends Controller
{
    public function index(Request $request): Response
    {
        $modules = QueryBuilder::for(Module::class)
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('sellable_as_addon'),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->withCount(['permissions', 'features'])
            ->with('prices')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Module $module) => [
                'id' => $module->id,
                'slug' => $module->slug,
                'name' => $module->name,
                'is_active' => $module->is_active,
                'sellable_as_addon' => $module->sellable_as_addon,
                'permissions_count' => $module->permissions_count,
                'features_count' => $module->features_count,
                'prices' => $module->prices->map(fn ($price) => [
                    'billing_period_label' => $price->billing_period->label,
                    'price' => $price->price,
                    'currency' => $price->currency,
                ]),
            ]);

        return Inertia::render('Central/Modules/Index', [
            'modules' => $modules,
            'stats' => [
                'total' => Module::count(),
                'sellableAsAddon' => Module::where('sellable_as_addon', true)->count(),
                'permissionsCount' => ModulePermission::count(),
            ],
            'can' => [
                'create' => $request->user()->can('central.modules.create'),
                'update' => $request->user()->can('central.modules.update'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/Modules/Create', $this->formOptions());
    }

    /**
     * @throws \Throwable
     */
    public function store(StoreModuleRequest $request): RedirectResponse
    {
        $module = DB::transaction(function () use ($request) {
            $module = Module::create($request->only('name', 'slug', 'sellable_as_addon'));

            $this->syncPrices($module, $request->input('prices', []));
            $this->syncPermissions($module, $request->input('permissions', []));

            return $module;
        });

        return redirect()->route('central.modules.index')->with('status', 'module-created-'.$module->slug);
    }

    public function edit(Module $module): Response
    {
        $module->load(['prices', 'permissions']);

        return Inertia::render('Central/Modules/Edit', [
            ...$this->formOptions(),
            'module' => [
                'id' => $module->id,
                'slug' => $module->slug,
                'name' => $module->name,
                'sellable_as_addon' => $module->sellable_as_addon,
                'prices' => $module->prices->mapWithKeys(fn ($price) => [
                    $price->billing_period->value => [
                        'price' => $price->price,
                        'currency' => $price->currency,
                    ],
                ]),
                'permissions' => $module->permissions
                    ->pluck('slug')
                    ->map(fn (string $slug) => Str::afterLast($slug, '.'))
                    ->map(fn (string $action) => Str::ucfirst($action)),
            ],
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function update(UpdateModuleRequest $request, Module $module): RedirectResponse
    {
        DB::transaction(function () use ($request, $module): void {
            $module->update($request->only('name', 'slug', 'sellable_as_addon'));

            $this->syncPrices($module, $request->input('prices', []));
            $this->syncPermissions($module, $request->input('permissions', []));
        });

        return redirect()->route('central.modules.index')->with('status', 'module-updated');
    }

    public function toggleActive(Module $module): RedirectResponse
    {
        $module->update(['is_active' => ! $module->is_active]);

        return back()->with('status', $module->is_active ? 'module-activated' : 'module-deactivated');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'billingPeriods' => BillingPeriod::toArray(),
            'actions' => Action::toArray(),
        ];
    }

    /**
     * @param  array<string, array{enabled?: bool, price?: string|null, currency?: string|null}>  $prices
     */
    private function syncPrices(Module $module, array $prices): void
    {
        foreach (BillingPeriod::cases() as $period) {
            $data = $prices[$period->value] ?? null;

            if (! $data || ! ($data['enabled'] ?? false) || ($data['price'] ?? null) === null || $data['price'] === '') {
                $module->prices()->where('billing_period', $period->value)->delete();

                continue;
            }

            $module->prices()->updateOrCreate(
                ['billing_period' => $period],
                ['price' => $data['price'], 'currency' => $data['currency'] ?: 'USD']
            );
        }
    }

    /**
     * @param  list<string>  $actionValues
     */
    private function syncPermissions(Module $module, array $actionValues): void
    {
        $slugs = collect($actionValues)
            ->map(fn (string $value) => "{$module->slug}.".strtolower($value))
            ->all();

        $module->permissions()->whereNotIn('slug', $slugs)->delete();

        foreach ($slugs as $slug) {
            $module->permissions()->firstOrCreate(['slug' => $slug]);
        }
    }
}
