<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;

function createUserTestTenant(): array
{
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $domain = $tenant->id.'.tenant-user-test.local';
    $tenant->createDomain($domain);

    return [$tenant, $domain];
}

function createUserTestOwner(): User
{
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    return $owner;
}

afterEach(function () {
    tenancy()->end();
});

test('a tenant user without permission cannot view the user directory', function () {
    [$tenant, $domain] = createUserTestTenant();

    $user = $tenant->run(fn () => User::factory()->create());

    $response = $this->actingAs($user, 'web')->get("http://{$domain}/users");

    $response->assertForbidden();

    $tenant->delete();
});

test('editing a tenant user syncs their roles', function () {
    [$tenant, $domain] = createUserTestTenant();

    [$owner, $member, $memberRoleId] = $tenant->run(function () {
        $owner = createUserTestOwner();
        $member = User::factory()->create();
        $memberRoleId = Role::where('name', 'member')->where('guard_name', 'web')->value('id');

        return [$owner, $member, $memberRoleId];
    });

    $response = $this->actingAs($owner, 'web')->patch("http://{$domain}/users/{$member->id}", [
        'roles' => [$memberRoleId],
    ]);

    $response->assertRedirect("http://{$domain}/users");

    $tenant->run(function () use ($member) {
        expect($member->fresh()->hasRole('member'))->toBeTrue();
    });

    $tenant->delete();
});

test('the last owner cannot lose their owner role', function () {
    [$tenant, $domain] = createUserTestTenant();

    $owner = $tenant->run(fn () => createUserTestOwner());

    $response = $this->actingAs($owner, 'web')->patch("http://{$domain}/users/{$owner->id}", [
        'roles' => [],
    ]);

    $response->assertSessionHasErrors('roles');

    $tenant->run(function () use ($owner) {
        expect($owner->fresh()->hasRole('owner'))->toBeTrue();
    });

    $tenant->delete();
});

test('an owner role can be removed when another owner remains', function () {
    [$tenant, $domain] = createUserTestTenant();

    [$owner, $secondOwner] = $tenant->run(function () {
        $owner = createUserTestOwner();
        $secondOwner = User::factory()->create();
        $secondOwner->assignRole('owner');

        return [$owner, $secondOwner];
    });

    $response = $this->actingAs($owner, 'web')->patch("http://{$domain}/users/{$owner->id}", [
        'roles' => [],
    ]);

    $response->assertRedirect("http://{$domain}/users");

    $tenant->run(function () use ($owner) {
        expect($owner->fresh()->hasRole('owner'))->toBeFalse();
    });

    $tenant->delete();
});

test('the users index supports search, sort and pagination', function () {
    [$tenant, $domain] = createUserTestTenant();

    $owner = $tenant->run(function () {
        User::factory()->count(20)->create();
        User::factory()->create(['name' => 'Zzyzx Person']);

        return createUserTestOwner();
    });

    $searched = $this->actingAs($owner, 'web')->get("http://{$domain}/users?filter[search]=Zzyzx");
    $searchedUsers = $searched->inertiaProps('users');
    expect($searchedUsers['total'])->toBe(1)
        ->and($searchedUsers['data'][0]['name'])->toBe('Zzyzx Person');

    $sorted = $this->actingAs($owner, 'web')->get("http://{$domain}/users?sort=name");
    $names = collect($sorted->inertiaProps('users')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($owner, 'web')->get("http://{$domain}/users");
    expect($paginated->inertiaProps('users')['data'])->toHaveCount(15);

    $tenant->delete();
});
