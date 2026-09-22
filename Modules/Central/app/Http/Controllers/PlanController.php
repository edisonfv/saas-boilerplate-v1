<?php

namespace Modules\Central\Http\Controllers;

use App\Enums\BillingPeriod;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\LimitType;
use App\Models\Module;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\StorePlanRequest;
use Modules\Central\Http\Requests\UpdatePlanRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = QueryBuilder::for(Plan::class)
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                AllowedFilter::exact('is_active'),
            )
            ->allowedSorts('name', 'created_at', 'trial_days')
            ->defaultSort('name')
            ->withCount(['modules', 'features'])
            ->with('prices')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Plan $plan) => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'is_active' => $plan->is_active,
                'trial_days' => $plan->trial_days,
                'modules_count' => $plan->modules_count,
                'features_count' => $plan->features_count,
                'prices' => $plan->prices->map(fn ($price) => [
                    'billing_period_label' => $price->billing_period->label,
                    'price' => $price->price,
                    'currency' => $price->currency,
                ]),
            ]);

        return Inertia::render('Central/Plans/Index', [
            'plans' => $plans,
            'stats' => [
                'total' => Plan::count(),
                'active' => Plan::where('is_active', true)->count(),
                'withTrial' => Plan::whereNotNull('trial_days')->count(),
            ],
            'can' => [
                'create' => $request->user()->can('central.plans.create'),
                'update' => $request->user()->can('central.plans.update'),
            ],
        ]);
    }

    public function show(Request $request, Plan $plan): Response
    {
        $plan->load(['prices', 'modules', 'features', 'limits']);

        return Inertia::render('Central/Plans/Show', [
            'can' => [
                'update' => $request->user()->can('central.plans.update'),
            ],
            'plan' => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'is_active' => $plan->is_active,
                'trial_days' => $plan->trial_days,
                'prices' => $plan->prices->map(fn ($price) => [
                    'billing_period_label' => $price->billing_period->label,
                    'price' => $price->price,
                    'currency' => $price->currency,
                ]),
                'modules' => $plan->modules->map(fn ($module) => [
                    'id' => $module->id,
                    'slug' => $module->slug,
                    'name' => $module->name,
                    'sellable_as_addon' => $module->sellable_as_addon,
                ]),
                'features' => $plan->features->map(fn ($feature) => [
                    'id' => $feature->id,
                    'slug' => $feature->slug,
                    'name' => $feature->name,
                ]),
                'limits' => $plan->limits->map(fn ($limit) => [
                    'id' => $limit->id,
                    'key' => $limit->key,
                    'name' => $limit->name,
                    'unit' => $limit->unit,
                    'value' => $limit->pivot->value,
                ]),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/Plans/Create', $this->formOptions());
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $plan = DB::transaction(function () use ($request) {
            $plan = Plan::create($request->only('name', 'slug', 'trial_days'));

            $this->syncPrices($plan, $request->input('prices', []));
            $plan->modules()->sync($request->input('modules', []));
            $plan->features()->sync($request->input('features', []));
            $this->syncLimits($plan, $request->input('limits', []));

            return $plan;
        });

        return redirect()->route('central.plans.show', $plan)->with('status', 'plan-created');
    }

    public function edit(Plan $plan): Response
    {
        $plan->load(['prices', 'modules', 'features', 'limits']);

        return Inertia::render('Central/Plans/Edit', [
            ...$this->formOptions(),
            'plan' => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'trial_days' => $plan->trial_days,
                'prices' => $plan->prices->mapWithKeys(fn ($price) => [
                    $price->billing_period->value => [
                        'price' => $price->price,
                        'currency' => $price->currency,
                    ],
                ]),
                'module_ids' => $plan->modules->pluck('id'),
                'feature_ids' => $plan->features->pluck('id'),
                'limits' => $plan->limits->mapWithKeys(fn ($limit) => [$limit->id => $limit->pivot->value]),
            ],
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        DB::transaction(function () use ($request, $plan): void {
            $plan->update($request->only('name', 'slug', 'trial_days'));

            $this->syncPrices($plan, $request->input('prices', []));
            $plan->modules()->sync($request->input('modules', []));
            $plan->features()->sync($request->input('features', []));
            $this->syncLimits($plan, $request->input('limits', []));
        });

        return redirect()->route('central.plans.show', $plan)->with('status', 'plan-updated');
    }

    public function toggleActive(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('status', $plan->is_active ? 'plan-activated' : 'plan-deactivated');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'modules' => Module::orderBy('name')->get(['id', 'name']),
            'features' => Feature::orderBy('name')->get(['id', 'name']),
            'limitTypes' => LimitType::orderBy('name')->get(['id', 'key', 'name', 'unit']),
            'billingPeriods' => BillingPeriod::toArray(),
        ];
    }

    /**
     * @param  array<string, array{enabled?: bool, price?: string|null, currency?: string|null}>  $prices
     */
    private function syncPrices(Plan $plan, array $prices): void
    {
        foreach (BillingPeriod::cases() as $period) {
            $data = $prices[$period->value] ?? null;

            if (! $data || ! ($data['enabled'] ?? false) || ($data['price'] ?? null) === null || $data['price'] === '') {
                $plan->prices()->where('billing_period', $period->value)->delete();

                continue;
            }

            $plan->prices()->updateOrCreate(
                ['billing_period' => $period],
                ['price' => $data['price'], 'currency' => $data['currency'] ?: 'USD']
            );
        }
    }

    /**
     * @param  array<int|string, int|string|null>  $limits
     */
    private function syncLimits(Plan $plan, array $limits): void
    {
        $sync = [];

        foreach ($limits as $limitTypeId => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $sync[(string) $limitTypeId] = ['value' => (int) $value];
        }

        $plan->limits()->sync($sync);
    }
}
