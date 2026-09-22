<?php

use App\Models\CentralUser;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('an unverified central user is redirected to the verification notice from the dashboard', function () {
    $user = CentralUser::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($user, 'central')->get(route('central.dashboard'));

    $response->assertRedirect(route('central.verification.notice'));
});

test('a verified central user visiting the notice is redirected to the dashboard', function () {
    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.verification.notice'));

    $response->assertRedirect(route('central.dashboard'));
});

test('visiting a valid signed verification link marks the email as verified', function () {
    Event::fake();

    $user = CentralUser::factory()->create(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute(
        'central.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user, 'central')->get($url);

    $response->assertRedirect(route('central.dashboard'));

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('an invalid signed verification link fails', function () {
    $user = CentralUser::factory()->create(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute(
        'central.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $response = $this->actingAs($user, 'central')->get($url);

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the verification notification can be resent', function () {
    $user = CentralUser::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($user, 'central')->post(route('central.verification.send'));

    $response->assertSessionHas('status', 'verification-link-sent');
});
