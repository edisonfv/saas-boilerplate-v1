<?php

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedSupport();
});

afterEach(function () {
    tenancy()->end();
});

test('a tenant with a running subscription can use the workspace', function () {
    [$tenant, $domain] = supportTenant();
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner, 'web')->get("http://{$domain}/dashboard")->assertOk();

    $tenant->delete();
});

test('the workspace is disabled once the subscription period ends', function () {
    [$tenant, $domain] = supportTenant();
    $owner = supportTenantUser($tenant);

    $this->travel(2)->months();

    $this->actingAs($owner, 'web')->get("http://{$domain}/dashboard")
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('General/AccessDisabled')
            ->where('reason', 'SubscriptionLapsed'));

    $this->actingAs($owner, 'web')->get("http://{$domain}/mi-soporte")->assertForbidden();

    $tenant->delete();
});

test('central staff can renew a lapsed subscription to re-enable the workspace', function () {
    [$tenant, $domain] = supportTenant();
    $owner = supportTenantUser($tenant);

    $this->travel(2)->months();

    $this->actingAs(supportStaff(), 'central')
        ->patch(route('central.tenants.renew', $tenant))
        ->assertSessionHas('status', 'subscription-renewed');

    $subscription = Subscription::where('tenant_id', $tenant->id)->sole();

    expect($subscription->status->equals(SubscriptionStatus::Active()))->toBeTrue()
        ->and($subscription->current_period_end->isFuture())->toBeTrue();

    $this->actingAs($owner, 'web')->get("http://{$domain}/dashboard")->assertOk();

    $tenant->delete();
});

test('renewing early extends from the current period end', function () {
    [$tenant] = supportTenant();
    $subscription = Subscription::where('tenant_id', $tenant->id)->sole();
    $originalEnd = $subscription->current_period_end;

    $this->actingAs(supportStaff(), 'central')->patch(route('central.tenants.renew', $tenant));

    expect($subscription->fresh()->current_period_end->equalTo($originalEnd->copy()->addMonth()))->toBeTrue();

    $tenant->delete();
});

test('the expire command marks lapsed subscriptions as expired', function () {
    [$tenant] = supportTenant();

    $this->artisan('subscriptions:expire-lapsed')->assertSuccessful();
    expect(Subscription::where('tenant_id', $tenant->id)->sole()->status->equals(SubscriptionStatus::Expired()))->toBeFalse();

    $this->travel(2)->months();

    $this->artisan('subscriptions:expire-lapsed')->assertSuccessful();
    expect(Subscription::where('tenant_id', $tenant->id)->sole()->status->equals(SubscriptionStatus::Expired()))->toBeTrue();

    $tenant->delete();
});
