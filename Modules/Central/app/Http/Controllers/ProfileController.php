<?php

namespace Modules\Central\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\ProfileUpdateRequest;

class ProfileController extends Controller
{
    /**
     * Show the platform staff's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Central/Profile/Edit', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the platform staff's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user('central');

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('central.profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the platform staff's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password:central'],
        ]);

        /** @var CentralUser $user */
        $user = $request->user('central');

        if ($user->hasRole('super-admin') && CentralUser::role('super-admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'password' => 'No puedes eliminar la única cuenta super-admin de la plataforma.',
            ]);
        }

        Auth::guard('central')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('central.login');
    }
}
