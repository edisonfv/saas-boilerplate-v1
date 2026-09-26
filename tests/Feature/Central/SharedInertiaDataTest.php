<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\CentralUser;
use Inertia\Testing\AssertableInertia as Assert;

test('a flashed status is forwarded as inertia flash data for the toast', function () {
    $user = CentralUser::factory()->create();

    $this->actingAs($user, 'central')
        ->withSession(['status' => 'profile-updated'])
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
        ])
        ->get(route('central.profile.edit'))
        ->assertOk()
        ->assertJsonPath('flash.status', 'profile-updated');
});

test('central pages share no tenant context', function () {
    $user = CentralUser::factory()->create();

    $this->actingAs($user, 'central')
        ->get(route('central.profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('tenant', null));
});
