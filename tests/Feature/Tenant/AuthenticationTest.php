<?php

use App\Models\Tenant;
use App\Models\User;

function createTenantWithDomain(): array
{
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $domain = $tenant->id.'.tenant-auth-test.local';
    $tenant->createDomain($domain);

    return [$tenant, $domain];
}

// Unlike $tenant->run(), an HTTP request through InitializeTenancyByDomain
// never calls tenancy()->end() afterwards (no such listener is registered in
// this app — see TenancyServiceProvider::events()). In production this is
// fine since each request is its own PHP process, but the whole test suite
// runs in a single process, so tenancy (and database.default) would leak
// into the next test unless we explicitly end it here.
afterEach(function () {
    tenancy()->end();
});

test('a tenant user can log in with valid credentials', function () {
    [$tenant, $domain] = createTenantWithDomain();

    $user = $tenant->run(fn () => User::factory()->create([
        'password' => bcrypt('password'),
    ]));

    $response = $this->post("http://{$domain}/login", [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect("http://{$domain}/dashboard");
    $this->assertAuthenticatedAs($user, 'web');

    $tenant->delete();
});

test('a tenant user cannot log in with invalid credentials', function () {
    [$tenant, $domain] = createTenantWithDomain();

    $user = $tenant->run(fn () => User::factory()->create([
        'password' => bcrypt('password'),
    ]));

    $response = $this->post("http://{$domain}/login", [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('web');

    $tenant->delete();
});

test('visiting a protected tenant route without a session redirects to the tenant login', function () {
    [$tenant, $domain] = createTenantWithDomain();

    $response = $this->get("http://{$domain}/dashboard");

    $response->assertRedirect("http://{$domain}/login");

    $tenant->delete();
});

test('a tenant user can log out, independently from the central guard', function () {
    [$tenant, $domain] = createTenantWithDomain();

    $user = $tenant->run(fn () => User::factory()->create());

    $response = $this->actingAs($user, 'web')
        ->post("http://{$domain}/logout");

    $response->assertRedirect("http://{$domain}/login");
    $this->assertGuest('web');

    $tenant->delete();
});
