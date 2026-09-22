<?php

use App\Models\CentralUser;

test('a central user can log in with valid credentials', function () {
    $user = CentralUser::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('central.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('central.dashboard'));
    $this->assertAuthenticatedAs($user, 'central');
});

test('a central user cannot log in with invalid credentials', function () {
    $user = CentralUser::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('central.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('central');
});

test('visiting a protected central route without a session redirects to the central login', function () {
    $response = $this->get(route('central.dashboard'));

    $response->assertRedirect(route('central.login'));
});

test('a central user can log out', function () {
    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->post(route('central.logout'));

    $response->assertRedirect(route('central.login'));
    $this->assertGuest('central');
});
