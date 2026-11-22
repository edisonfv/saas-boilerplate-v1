<?php

namespace Modules\Central\Providers;

use App\Http\Middleware\PreventAccessFromTenantDomains;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Central';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * Central is the one module that is NOT tenant-scoped: its routes resolve
     * only on the central domain, with plain "web" middleware — no tenancy
     * bootstrapping. This is the documented exception; every other module's
     * RouteServiceProvider wires routes/tenant.php with the tenancy middleware
     * instead (see stubs/nwidart-stubs/route-provider.stub).
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', PreventAccessFromTenantDomains::class])->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}
