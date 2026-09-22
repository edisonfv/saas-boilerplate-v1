<?php

namespace App\Tenancy\Bootstrappers;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;

/**
 * Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper always wraps cache
 * calls in Cache::tags(...), which the "database" cache store (this
 * project's CACHE_STORE) doesn't support. We don't need tag-based isolation
 * anyway: DatabaseTenancyBootstrapper already swaps the default DB
 * connection per tenant, so the "database" cache store's own table is
 * already physically isolated per tenant.
 *
 * What we do need is this class: Illuminate's CacheManager memoizes each
 * resolved store (including the Connection it was built with) for the
 * lifetime of the container. If anything resolves the "database" cache
 * store before tenancy switches the connection (e.g. RateLimiter, or a
 * request served by a persistent `php artisan serve` worker that reuses the
 * container across requests), that store keeps writing to the previously
 * bound connection even after DatabaseTenancyBootstrapper switches
 * database.default. Rebuilding the CacheManager (and clearing the Cache
 * facade's resolved instance) on every tenancy switch forces a fresh store
 * bound to the connection that's active right now.
 */
class CacheTenancyBootstrapper implements TenancyBootstrapper
{
    protected ?CacheManager $originalCache = null;

    public function __construct(protected Application $app) {}

    public function bootstrap(Tenant $tenant): void
    {
        Cache::clearResolvedInstances();

        $this->originalCache ??= $this->app->make('cache');
        $this->app->extend('cache', fn () => new CacheManager($this->app));
        $this->resetPermissionRegistrar();
    }

    public function revert(): void
    {
        Cache::clearResolvedInstances();

        $this->app->extend('cache', fn () => $this->originalCache);
        $this->originalCache = null;
        $this->resetPermissionRegistrar();
    }

    private function resetPermissionRegistrar(): void
    {
        if (! class_exists(PermissionRegistrar::class)) {
            return;
        }

        if ($this->app->resolved(PermissionRegistrar::class)) {
            $this->app->make(PermissionRegistrar::class)->clearPermissionsCollection();
        }

        $this->app->forgetInstance(PermissionRegistrar::class);
    }
}
