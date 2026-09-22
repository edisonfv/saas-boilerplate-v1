<?php

use App\Http\Middleware\EnsureTenantHasFeature;
use App\Http\Middleware\EnsureTenantHasModule;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'tenant.active' => EnsureTenantIsActive::class,
            'tenant.module' => EnsureTenantHasModule::class,
            'tenant.feature' => EnsureTenantHasFeature::class,
            // spatie/laravel-permission doesn't register these aliases
            // itself — it only registers the Route::can()-style macros.
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // The "appearance" cookie is set as plain text by client-side JS
        // (useAppearance.ts), not encrypted by Laravel. EncryptCookies
        // otherwise tries to decrypt every incoming cookie and silently
        // discards ones that fail, so HandleAppearance would never see it.
        $middleware->encryptCookies(except: ['appearance']);

        // Central and tenant requests use different guards and route names
        // (see App\Models\CentralUser vs App\Models\User) — pick the right
        // login/dashboard route depending on which domain the request is on,
        // since Laravel's defaults ("login"/"dashboard") don't exist here.
        // Must be set here (not at file scope) because withMiddleware()
        // itself sets a default redirectGuestsTo('login') lazily on first
        // HttpKernel resolution, which would otherwise overwrite it.
        $middleware->redirectGuestsTo(fn () => tenant() ? route('tenant.login') : route('central.login'));
        $middleware->redirectUsersTo(fn () => tenant() ? route('tenant.dashboard') : route('central.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (TenantCouldNotBeIdentifiedException $e) => abort(404));
    })->create();
