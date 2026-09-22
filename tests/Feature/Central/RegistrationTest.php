<?php

use App\Models\CentralUser;
use Database\Seeders\CentralAclSeeder;

test('a guest cannot reach the staff registration page', function () {
    $response = $this->get(route('central.register'));

    $response->assertRedirect(route('central.login'));
});

test('a central user without the staff.manage permission cannot register new staff', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();
    $user->assignRole('support');

    $response = $this->actingAs($user, 'central')->get(route('central.register'));

    $response->assertForbidden();
});

test('a super-admin can register a new staff member with a role', function () {
    $this->seed(CentralAclSeeder::class);

    $admin = CentralUser::firstWhere('email', 'admin@example.com');

    $response = $this->actingAs($admin, 'central')->post(route('central.register'), [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'support',
    ]);

    $response->assertRedirect(route('central.dashboard'));

    $newUser = CentralUser::firstWhere('email', 'staffer@example.com');

    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole('support'))->toBeTrue()
        ->and($newUser->email_verified_at)->toBeNull();

    $this->assertAuthenticatedAs($admin, 'central');
});

test('registering a new staff member requires a valid role', function () {
    $this->seed(CentralAclSeeder::class);

    $admin = CentralUser::firstWhere('email', 'admin@example.com');

    $response = $this->actingAs($admin, 'central')->post(route('central.register'), [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'not-a-real-role',
    ]);

    $response->assertSessionHasErrors('role');
});
