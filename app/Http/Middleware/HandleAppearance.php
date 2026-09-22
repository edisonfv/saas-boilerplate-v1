<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the "appearance" cookie (light/dark/system) with the Blade root
 * view so app.blade.php can set the `dark` class on <html> before Vue
 * mounts. This is a Blade view variable, not an Inertia prop — Inertia
 * shared props aren't available until the SPA hydrates.
 */
class HandleAppearance
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance', 'system'));

        return $next($request);
    }
}
