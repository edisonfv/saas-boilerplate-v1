<?php

use App\Models\CentralUser;
use Database\Seeders\CentralAclSeeder;
use Illuminate\Support\Facades\Hash;

test('a central user can update their name and email', function () {
    $user = CentralUser::factory()->create(['email' => 'old@example.com']);

    $response = $this->actingAs($user, 'central')->patch(route('central.profile.update'), [
        'name' => 'Updated Name',
        'email' => 'new@example.com',
    ]);

    $response->assertRedirect(route('central.profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('updating the profile without changing the email keeps it verified', function () {
    $user = CentralUser::factory()->create(['email' => 'same@example.com']);

    $this->actingAs($user, 'central')->patch(route('central.profile.update'), [
        'name' => 'Updated Name',
        'email' => 'same@example.com',
    ]);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('a central user can change their password by providing the current one', function () {
    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->put(route('central.password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('central.profile.edit'));
    $response->assertSessionDoesntHaveErrors();

    $this->assertAuthenticatedAs($user, 'central');
    $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
});

test('changing the password fails with the wrong current password', function () {
    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->put(route('central.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('current_password');
});

test('a central user can delete their own account after confirming their password', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();
    $user->assignRole('support');

    $this->actingAs($user, 'central')->post(route('central.password.confirm'), [
        'password' => 'password',
    ]);

    $response = $this->actingAs($user, 'central')->delete(route('central.profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('central.login'));
    $this->assertGuest('central');
    $this->assertDatabaseMissing('central_users', ['id' => $user->id]);
});

test('the last super-admin cannot delete their own account', function () {
    $this->seed(CentralAclSeeder::class);

    $admin = CentralUser::firstWhere('email', 'admin@example.com');

    $this->actingAs($admin, 'central')->post(route('central.password.confirm'), [
        'password' => 'password',
    ]);

    $response = $this->actingAs($admin, 'central')->delete(route('central.profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseHas('central_users', ['id' => $admin->id]);
});
