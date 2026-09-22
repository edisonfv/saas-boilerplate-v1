<?php

use App\Enums\TenantStatus;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Models\Tenant;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

test('the middleware aborts suspended tenants', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid(), 'status' => TenantStatus::Suspended()]);

    $tenant->run(function () {
        $middleware = app(EnsureTenantIsActive::class);

        expect(fn () => $middleware->handle(request(), fn () => response('passed')))
            ->toThrow(HttpException::class);
    });

    $tenant->delete();
});
