<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Modules\Central\Http\Requests\UpdatePasswordRequest;

class PasswordController extends Controller
{
    /**
     * Update the platform staff's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user('central')->update([
            'password' => Hash::make($request->string('password')),
        ]);

        return redirect()->route('central.profile.edit')->with('status', 'password-updated');
    }
}
