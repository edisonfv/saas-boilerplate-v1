<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Resend the platform staff email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user('central')->hasVerifiedEmail()) {
            return redirect()->route('central.dashboard');
        }

        $request->user('central')->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
