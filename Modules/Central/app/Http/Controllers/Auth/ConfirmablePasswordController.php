<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the platform staff password confirmation page.
     */
    public function show(): Response
    {
        return Inertia::render('Central/Auth/ConfirmPassword');
    }

    /**
     * Confirm the platform staff's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('central')->validate([
            'email' => $request->user('central')->email,
            'password' => $request->string('password'),
        ])) {
            throw ValidationException::withMessages([
                'password' => trans('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('central.dashboard'));
    }
}
