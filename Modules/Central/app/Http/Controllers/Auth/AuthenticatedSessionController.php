<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\CentralLoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the platform staff login page.
     */
    public function create(): Response
    {
        return Inertia::render('Central/Auth/Login');
    }

    /**
     * Authenticate a platform staff session.
     */
    public function store(CentralLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->route('central.dashboard');
    }

    /**
     * Destroy the platform staff session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('central')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('central.login');
    }
}
