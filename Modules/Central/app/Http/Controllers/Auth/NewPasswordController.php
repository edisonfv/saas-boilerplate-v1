<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\NewPasswordRequest;

class NewPasswordController extends Controller
{
    /**
     * Show the platform staff reset-password page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Central/Auth/ResetPassword', [
            'email' => $request->string('email')->toString(),
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Reset the platform staff's password.
     */
    public function store(NewPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker('central_users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (CentralUser $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => trans($status),
            ]);
        }

        return redirect()->route('central.login')->with('status', trans($status));
    }
}
