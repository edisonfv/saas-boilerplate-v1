<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LimitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\StoreLimitTypeRequest;
use Modules\Central\Http\Requests\UpdateLimitTypeRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LimitTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $limitTypes = QueryBuilder::for(LimitType::class)
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                AllowedFilter::exact('is_active'),
            )
            ->allowedSorts('name', 'key', 'created_at')
            ->defaultSort('name')
            ->withCount('plans')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LimitType $limitType) => [
                'id' => $limitType->id,
                'key' => $limitType->key,
                'name' => $limitType->name,
                'unit' => $limitType->unit,
                'is_active' => $limitType->is_active,
                'plans_count' => $limitType->plans_count,
            ]);

        return Inertia::render('Central/LimitTypes/Index', [
            'limitTypes' => $limitTypes,
            'can' => [
                'create' => $request->user()->can('central.limit-types.create'),
                'update' => $request->user()->can('central.limit-types.update'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/LimitTypes/Create');
    }

    public function store(StoreLimitTypeRequest $request): RedirectResponse
    {
        LimitType::create($request->only('key', 'name', 'unit'));

        return redirect()->route('central.limit-types.index')->with('status', 'limit-type-created');
    }

    public function edit(LimitType $limitType): Response
    {
        return Inertia::render('Central/LimitTypes/Edit', [
            'limitType' => [
                'id' => $limitType->id,
                'key' => $limitType->key,
                'name' => $limitType->name,
                'unit' => $limitType->unit,
            ],
        ]);
    }

    public function update(UpdateLimitTypeRequest $request, LimitType $limitType): RedirectResponse
    {
        $limitType->update($request->only('key', 'name', 'unit'));

        return redirect()->route('central.limit-types.index')->with('status', 'limit-type-updated');
    }

    public function toggleActive(LimitType $limitType): RedirectResponse
    {
        $limitType->update(['is_active' => ! $limitType->is_active]);

        return back()->with('status', $limitType->is_active ? 'limit-type-activated' : 'limit-type-deactivated');
    }
}
