<?php

use App\Models\Plan;
use App\Models\PlanPrice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

/**
 * @return array<string, int>
 */
function centralRowCounts(): array
{
    return collect([
        'modules', 'module_permissions', 'module_prices', 'features', 'limit_types',
        'plans', 'plan_prices', 'plan_module', 'plan_feature', 'plan_limit',
        'signature_products', 'signature_packages', 'support_business_hours', 'support_attendance_types',
        config('permission.table_names.roles'), config('permission.table_names.permissions'),
        config('permission.table_names.role_has_permissions'),
    ])->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
}

test('running the central seeders twice changes nothing', function () {
    $this->seed(DatabaseSeeder::class);
    $firstRun = centralRowCounts();

    $this->seed(DatabaseSeeder::class);

    expect(centralRowCounts())->toBe($firstRun)
        ->and($firstRun['plans'])->toBeGreaterThan(0);
});

test('re-seeding keeps what an admin edited in the catalog', function () {
    $this->seed(DatabaseSeeder::class);

    $pro = Plan::where('slug', 'pro')->sole();
    $pro->update(['name' => 'Profesional']);
    PlanPrice::where('plan_id', $pro->id)->update(['price' => 99]);
    $usersLimit = $pro->limits()->where('key', 'users')->sole();
    $pro->limits()->updateExistingPivot($usersLimit->id, ['value' => 7]);

    $this->seed(DatabaseSeeder::class);

    expect($pro->fresh()->name)->toBe('Profesional')
        ->and(PlanPrice::where('plan_id', $pro->id)->get()->map(fn (PlanPrice $price) => (float) $price->price)->unique()->values()->all())->toBe([99.0])
        ->and((int) $pro->limits()->where('key', 'users')->sole()->pivot->value)->toBe(7);
});
