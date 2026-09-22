<?php

use App\Enums\BillingPeriod;
use App\Enums\TenantStatus;
use App\Models\CentralUser;
use App\Models\Module;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CentralAclSeeder;
use Illuminate\Support\Facades\Hash;

function tenantManager(): CentralUser
{
    /** @var CentralUser $user */
    $user = CentralUser::factory()->create();
    $user->givePermissionTo('central.tenants.create');

    return $user;
}

afterEach(function () {
    tenancy()->end();
});

test('a central user without permission cannot create tenants', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.tenants.create'));

    $response->assertForbidden();
});

test('creating a tenant provisions its database, domain and subscription', function () {
    $this->seed(CentralAclSeeder::class);

    $module = Module::factory()->create();
    $plan = Plan::factory()->create();
    PlanPrice::factory()->create([
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly(),
    ]);
    $plan->modules()->attach($module->id);

    $tenantId = 'acme-'.uniqid();
    $password = 'Strong-owner-password-123!';

    $response = $this->actingAs(tenantManager(), 'central')->post(route('central.tenants.store'), [
        'id' => $tenantId,
        'company_name' => 'Acme Corp',
        'legal_name' => 'Acme Corp S.A.',
        'tax_identifier' => '0999999999001',
        'country_code' => 'ec',
        'timezone' => 'America/Guayaquil',
        'owner_name' => 'Tenant Owner',
        'owner_email' => 'OWNER@ACME.TEST',
        'owner_password' => $password,
        'owner_password_confirmation' => $password,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly()->value,
    ]);

    $tenant = Tenant::find($tenantId);

    $response->assertRedirect(route('central.tenants.show', $tenant));

    expect($tenant)->not->toBeNull()
        ->and($tenant->company_name)->toBe('Acme Corp')
        ->and($tenant->country_code)->toBe('EC')
        ->and($tenant->primary_contact_email)->toBe('owner@acme.test')
        ->and($tenant->status->equals(TenantStatus::Active()))->toBeTrue()
        ->and($tenant->provisioned_at)->not->toBeNull()
        ->and($tenant->domains()->count())->toBe(1)
        ->and($tenant->domains()->first()->domain)->toBe("{$tenantId}.".config('tenancy.central_domains.0'))
        ->and($tenant->subscription()->exists())->toBeTrue()
        ->and($tenant->subscription->modules()->count())->toBe(1);

    $tenant->run(function () use ($password) {
        $owner = User::where('email', 'owner@acme.test')->sole();

        expect(Hash::check($password, $owner->password))->toBeTrue()
            ->and($owner->hasRole('owner'))->toBeTrue();
    });

    $tenant->delete();
});

test('creating a tenant with a duplicate identifier fails validation without leaving an orphaned row', function () {
    $this->seed(CentralAclSeeder::class);

    $plan = Plan::factory()->create();
    PlanPrice::factory()->create([
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly(),
    ]);
    $tenantId = 'dup-'.uniqid();
    $password = 'Strong-owner-password-123!';

    $existing = Tenant::create(['id' => $tenantId]);

    $response = $this->actingAs(tenantManager(), 'central')->post(route('central.tenants.store'), [
        'id' => $tenantId,
        'company_name' => 'Duplicate Corp',
        'country_code' => 'EC',
        'timezone' => 'America/Guayaquil',
        'owner_name' => 'Tenant Owner',
        'owner_email' => 'owner-dup@example.test',
        'owner_password' => $password,
        'owner_password_confirmation' => $password,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly()->value,
    ]);

    $response->assertSessionHasErrors('id');
    expect(Tenant::where('id', $tenantId)->count())->toBe(1);

    $existing->delete();
});

test('creating a tenant requires an active price for the selected billing period', function () {
    $this->seed(CentralAclSeeder::class);

    $plan = Plan::factory()->create();
    $password = 'Strong-owner-password-123!';

    $response = $this->actingAs(tenantManager(), 'central')->post(route('central.tenants.store'), [
        'id' => 'noprice-'.uniqid(),
        'company_name' => 'No Price Corp',
        'country_code' => 'EC',
        'timezone' => 'America/Guayaquil',
        'owner_name' => 'Tenant Owner',
        'owner_email' => 'owner-noprice@example.test',
        'owner_password' => $password,
        'owner_password_confirmation' => $password,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly()->value,
    ]);

    $response->assertSessionHasErrors('billing_period');
});

test('the tenants index supports search, sort and pagination', function () {
    $this->seed(CentralAclSeeder::class);

    // Bypass the TenantCreated event pipeline (real DB provisioning) — this
    // test only exercises the central `tenants` table listing query, not
    // multi-tenant provisioning.
    Tenant::withoutEvents(function () {
        for ($i = 0; $i < 20; $i++) {
            Tenant::create(['id' => 'tenant-'.uniqid()]);
        }

        Tenant::create(['id' => 'zzyzx-tenant']);
    });

    $viewer = CentralUser::factory()->create();
    $viewer->givePermissionTo('central.tenants.view');

    $searched = $this->actingAs($viewer, 'central')->get(route('central.tenants.index', ['filter' => ['search' => 'zzyzx']]));
    $searchedTenants = $searched->inertiaProps('tenants');
    expect($searchedTenants['total'])->toBe(1)
        ->and($searchedTenants['data'][0]['id'])->toBe('zzyzx-tenant');

    $sorted = $this->actingAs($viewer, 'central')->get(route('central.tenants.index', ['sort' => 'id']));
    $ids = collect($sorted->inertiaProps('tenants')['data'])->pluck('id');
    expect($ids->all())->toBe($ids->sort()->values()->all());

    $paginated = $this->actingAs($viewer, 'central')->get(route('central.tenants.index'));
    expect($paginated->inertiaProps('tenants')['data'])->toHaveCount(15);
});

test('the tenants index tolerates legacy lowercase tenant statuses', function () {
    $this->seed(CentralAclSeeder::class);

    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'legacy-'.uniqid(),
        'status' => 'active',
    ]));

    $viewer = CentralUser::factory()->create();
    $viewer->givePermissionTo('central.tenants.view');

    $response = $this->actingAs($viewer, 'central')->get(route('central.tenants.index', [
        'filter' => ['search' => $tenant->id],
    ]));

    $row = $response->inertiaProps('tenants')['data'][0];

    expect($row['tenant_status'])->toBe(TenantStatus::Active()->value)
        ->and($row['tenant_status_label'])->toBe(TenantStatus::Active()->label);

    $tenant->delete();
});
