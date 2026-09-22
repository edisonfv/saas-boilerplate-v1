<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the platform staff email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user('central')->hasVerifiedEmail()
            ? redirect()->route('central.dashboard')
            : Inertia::render('Central/Auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
