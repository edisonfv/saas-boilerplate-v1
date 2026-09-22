<?php

namespace Modules\General\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\General\Http\Requests\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the tenant login page.
     */
    public function create(): Response
    {
        return Inertia::render('General/Auth/Login');
    }

    /**
     * Authenticate a tenant user session.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->route('tenant.dashboard');
    }

    /**
     * Destroy the tenant user session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
