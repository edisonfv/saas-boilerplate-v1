<?php

use App\Models\CentralUser;
use Database\Seeders\CentralAclSeeder;
use App\Models\Role;

function staffManager(): CentralUser
{
    /** @var CentralUser $user */
    $user = CentralUser::factory()->create();
    $user->givePermissionTo(['central.staff.view', 'central.staff.update']);

    return $user;
}

test('a central user without permission cannot view the staff directory', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.staff.index'));

    $response->assertForbidden();
});

test('editing a staff member syncs their roles', function () {
    $this->seed(CentralAclSeeder::class);

    $manager = staffManager();
    $member = CentralUser::factory()->create();
    $member->assignRole('sales');

    $billingRoleId = Role::where('name', 'billing')->where('guard_name', 'central')->value('id');

    $response = $this->actingAs($manager, 'central')->patch(route('central.staff.update', $member), [
        'roles' => [$billingRoleId],
    ]);

    $response->assertRedirect(route('central.staff.index'));

    $member->refresh();
    expect($member->hasRole('billing'))->toBeTrue()
        ->and($member->hasRole('sales'))->toBeFalse();
});

test('the last super-admin cannot lose their super-admin role', function () {
    $this->seed(CentralAclSeeder::class);

    $manager = staffManager();
    $admin = CentralUser::firstWhere('email', 'admin@example.com');

    $response = $this->actingAs($manager, 'central')->patch(route('central.staff.update', $admin), [
        'roles' => [],
    ]);

    $response->assertSessionHasErrors('roles');
    expect($admin->fresh()->hasRole('super-admin'))->toBeTrue();
});

test('a super-admin role can be removed when another super-admin remains', function () {
    $this->seed(CentralAclSeeder::class);

    $manager = staffManager();
    $admin = CentralUser::firstWhere('email', 'admin@example.com');

    $secondAdmin = CentralUser::factory()->create();
    $secondAdmin->assignRole('super-admin');

    $response = $this->actingAs($manager, 'central')->patch(route('central.staff.update', $admin), [
        'roles' => [],
    ]);

    $response->assertRedirect(route('central.staff.index'));
    expect($admin->fresh()->hasRole('super-admin'))->toBeFalse();
});

test('the staff index supports search, sort and pagination', function () {
    $this->seed(CentralAclSeeder::class);

    CentralUser::factory()->count(20)->create();
    CentralUser::factory()->create(['name' => 'Zzyzx Person']);

    $viewer = staffManager();

    $searched = $this->actingAs($viewer, 'central')->get(route('central.staff.index', ['filter' => ['search' => 'Zzyzx']]));
    $searchedStaff = $searched->inertiaProps('staff');
    expect($searchedStaff['total'])->toBe(1)
        ->and($searchedStaff['data'][0]['name'])->toBe('Zzyzx Person');

    $sorted = $this->actingAs($viewer, 'central')->get(route('central.staff.index', ['sort' => 'name']));
    $names = collect($sorted->inertiaProps('staff')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($viewer, 'central')->get(route('central.staff.index'));
    expect($paginated->inertiaProps('staff')['data'])->toHaveCount(15);
});
