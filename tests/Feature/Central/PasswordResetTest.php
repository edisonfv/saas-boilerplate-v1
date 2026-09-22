<?php

use App\Models\CentralUser;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('a password reset link can be requested', function () {
    Notification::fake();

    $user = CentralUser::factory()->create();

    $response = $this->post(route('central.password.email'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('a password can be reset with a valid token', function () {
    Notification::fake();

    $user = CentralUser::factory()->create();

    $this->post(route('central.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $response = $this->post(route('central.password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('central.login'));

        return true;
    });

    $loginResponse = $this->post(route('central.login'), [
        'email' => $user->email,
        'password' => 'new-password',
    ]);

    $loginResponse->assertRedirect(route('central.dashboard'));
});

test('a password cannot be reset with an invalid token', function () {
    $user = CentralUser::factory()->create();

    $response = $this->post(route('central.password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');
});
