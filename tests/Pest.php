<?php

use App\Enums\BillingPeriod;
use App\Models\CentralUser;
use App\Models\Plan;
use App\Models\SupportTechnician;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CentralAclSeeder;
use Database\Seeders\GeneralModuleSeeder;
use Database\Seeders\SupportModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Support module helpers
|--------------------------------------------------------------------------
*/

/**
 * Seeds the Support module + example catalog (Pro bundles Support with
 * 4 included hours, 2 simultaneous appointments and a 4h/24h SLA) and
 * freezes time on a Monday morning inside business hours.
 */
function seedSupport(): void
{
    Carbon::setTestNow('2026-09-28 09:00:00');

    test()->seed(GeneralModuleSeeder::class);
    test()->seed(SupportModuleSeeder::class);
    test()->seed(CatalogSeeder::class);
    test()->seed(CentralAclSeeder::class);
}

/**
 * A tenant with a domain, subscribed to the given plan ("pro" includes Support).
 *
 * @return array{0: Tenant, 1: string}
 */
function supportTenant(string $planSlug = 'pro'): array
{
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid(), 'company_name' => 'Acme S.A.']);
    $domain = $tenant->id.'.support-test.local';
    $tenant->createDomain($domain);

    app(TenantPlanSubscriber::class)->subscribe(
        $tenant,
        Plan::where('slug', $planSlug)->sole(),
        BillingPeriod::Monthly(),
    );

    return [$tenant, $domain];
}

/**
 * A tenant user; "owner" gets every permission of the tenant's modules
 * (including tenant.support-tickets.view-all).
 *
 * @param  list<string>  $permissions
 */
function supportTenantUser(Tenant $tenant, ?string $role = 'owner', array $permissions = []): User
{
    return $tenant->run(function () use ($role, $permissions) {
        $user = User::factory()->create();

        if ($role !== null) {
            $user->assignRole($role);
        }

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    });
}

function supportStaff(string $role = 'super-admin'): CentralUser
{
    $staff = CentralUser::factory()->create();
    $staff->assignRole($role);

    return $staff;
}

/**
 * A technician on shift Mon–Fri 08:00–20:00.
 */
function supportTechnician(int $capacity = 1, ?CentralUser $user = null): SupportTechnician
{
    $technician = SupportTechnician::create([
        'central_user_id' => ($user ?? supportStaff('support'))->id,
        'capacity' => $capacity,
    ]);

    foreach (range(1, 5) as $weekday) {
        $technician->shifts()->create(['weekday' => $weekday, 'starts_at' => '08:00', 'ends_at' => '20:00']);
    }

    return $technician;
}
