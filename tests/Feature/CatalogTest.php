<?php

use App\Enums\BillingPeriod;
use App\Models\Feature;
use App\Models\LimitType;
use App\Models\Module;
use App\Models\Plan;
use Illuminate\Database\QueryException;

test('a plan can have multiple prices with different billing periods cast to the enum', function () {
    $plan = Plan::factory()->create();
    $plan->prices()->createMany([
        ['billing_period' => BillingPeriod::Monthly(), 'price' => 29, 'currency' => 'USD'],
        ['billing_period' => BillingPeriod::Annual(), 'price' => 290, 'currency' => 'USD'],
    ]);

    expect($plan->prices()->count())->toBe(2);

    $monthly = $plan->prices()->where('billing_period', BillingPeriod::Monthly()->value)->sole();

    expect($monthly->billing_period)->toBeInstanceOf(BillingPeriod::class)
        ->and($monthly->billing_period->equals(BillingPeriod::Monthly()))->toBeTrue();
});

test('billing period enum exposes a display label distinct from its comparison value', function () {
    $monthly = BillingPeriod::Monthly();

    expect($monthly->value)->toBe('Monthly')
        ->and($monthly->label)->toBe('Mensual')
        ->and($monthly->equals(BillingPeriod::Monthly()))->toBeTrue()
        ->and(BillingPeriod::toArray())->toBe([
            'Monthly' => 'Mensual',
            'Quarterly' => 'Trimestral',
            'Annual' => 'Anual',
        ]);
});

test('a plan can be attached modules, features and limits with pivot values', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $feature = Feature::factory()->create();
    $limitType = LimitType::factory()->create(['key' => 'users']);

    $plan->modules()->attach($module);
    $plan->features()->attach($feature);
    $plan->limits()->attach($limitType, ['value' => 5]);

    expect($plan->modules()->first()->is($module))->toBeTrue()
        ->and($plan->features()->first()->is($feature))->toBeTrue()
        ->and($plan->limits()->first()->pivot->value)->toBe(5);
});

test('a module marked as sellable as addon has its own prices', function () {
    $module = Module::factory()->sellableAsAddon()->create();
    $module->prices()->create([
        'billing_period' => BillingPeriod::Monthly(),
        'price' => 15,
        'currency' => 'USD',
    ]);

    expect($module->sellable_as_addon)->toBeTrue()
        ->and($module->prices()->count())->toBe(1);
});

test('module slug is unique', function () {
    Module::factory()->create(['slug' => 'crm']);

    expect(fn () => Module::factory()->create(['slug' => 'crm']))
        ->toThrow(QueryException::class);
});

test('plan price billing period is unique per plan', function () {
    $plan = Plan::factory()->create();
    $plan->prices()->create(['billing_period' => BillingPeriod::Monthly(), 'price' => 29, 'currency' => 'USD']);

    expect(fn () => $plan->prices()->create(['billing_period' => BillingPeriod::Monthly(), 'price' => 39, 'currency' => 'USD']))
        ->toThrow(QueryException::class);
});
