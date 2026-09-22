<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\StoreFeatureRequest;
use Modules\Central\Http\Requests\UpdateFeatureRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FeatureController extends Controller
{
    public function index(Request $request): Response
    {
        $features = QueryBuilder::for(Feature::class)
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('module_id'),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->withCount('plans')
            ->with('module')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Feature $feature) => [
                'id' => $feature->id,
                'slug' => $feature->slug,
                'name' => $feature->name,
                'is_active' => $feature->is_active,
                'module_name' => $feature->module?->name,
                'plans_count' => $feature->plans_count,
            ]);

        return Inertia::render('Central/Features/Index', [
            'features' => $features,
            'can' => [
                'create' => $request->user()->can('central.features.create'),
                'update' => $request->user()->can('central.features.update'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/Features/Create', $this->formOptions());
    }

    public function store(StoreFeatureRequest $request): RedirectResponse
    {
        Feature::create($request->only('name', 'slug', 'module_id'));

        return redirect()->route('central.features.index')->with('status', 'feature-created');
    }

    public function edit(Feature $feature): Response
    {
        return Inertia::render('Central/Features/Edit', [
            ...$this->formOptions(),
            'feature' => [
                'id' => $feature->id,
                'slug' => $feature->slug,
                'name' => $feature->name,
                'module_id' => $feature->module_id,
            ],
        ]);
    }

    public function update(UpdateFeatureRequest $request, Feature $feature): RedirectResponse
    {
        $feature->update($request->only('name', 'slug', 'module_id'));

        return redirect()->route('central.features.index')->with('status', 'feature-updated');
    }

    public function toggleActive(Feature $feature): RedirectResponse
    {
        $feature->update(['is_active' => ! $feature->is_active]);

        return back()->with('status', $feature->is_active ? 'feature-activated' : 'feature-deactivated');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'modules' => Module::orderBy('name')->get(['id', 'name']),
        ];
    }
}
