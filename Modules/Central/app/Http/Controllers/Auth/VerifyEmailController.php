<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the platform staff's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user('central')->hasVerifiedEmail()) {
            return redirect()->route('central.dashboard');
        }

        $request->fulfill();

        return redirect()->route('central.dashboard')->with('status', 'verified');
    }
}
