<?php

use App\Models\CentralUser;

test('deleting the account without a recent password confirmation redirects to the confirm page', function () {
    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->delete(route('central.profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('central.password.confirm'));
    $this->assertDatabaseHas('central_users', ['id' => $user->id]);
});

test('confirming with the wrong password fails', function () {
    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->post(route('central.password.confirm'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
});

test('confirming with the correct password allows the sensitive action to proceed', function () {
    $user = CentralUser::factory()->create();

    $this->actingAs($user, 'central')->post(route('central.password.confirm'), [
        'password' => 'password',
    ])->assertRedirect(route('central.dashboard'));

    $response = $this->actingAs($user, 'central')->delete(route('central.profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('central.login'));
    $this->assertDatabaseMissing('central_users', ['id' => $user->id]);
});
