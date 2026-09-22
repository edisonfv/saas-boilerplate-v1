<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\PasswordResetLinkRequest;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the platform staff forgot-password page.
     */
    public function create(): Response
    {
        return Inertia::render('Central/Auth/ForgotPassword');
    }

    /**
     * Send a password reset link to the given platform staff email.
     */
    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        $status = Password::broker('central_users')->sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => trans($status),
            ]);
        }

        return back()->with('status', trans($status));
    }
}
