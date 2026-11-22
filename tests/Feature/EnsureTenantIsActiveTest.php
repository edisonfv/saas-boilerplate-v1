<?php

use App\Enums\TenantStatus;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Models\Tenant;

afterEach(function () {
    tenancy()->end();
});

test('the middleware allows active tenants', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid(), 'status' => TenantStatus::Active()]);

    $tenant->run(function () {
        $middleware = app(EnsureTenantIsActive::class);

        $result = $middleware->handle(request(), fn () => response('passed'));

        expect($result->getContent())->toBe('passed');
    });

    $tenant->delete();
});

test('the middleware blocks suspended tenants with the disabled page', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid(), 'status' => TenantStatus::Suspended()]);

    $tenant->run(function () {
        $middleware = app(EnsureTenantIsActive::class);

        $result = $middleware->handle(request(), fn () => response('passed'));

        expect($result->getStatusCode())->toBe(403)
            ->and($result->getContent())->not->toBe('passed');
    });

    $tenant->delete();
});
