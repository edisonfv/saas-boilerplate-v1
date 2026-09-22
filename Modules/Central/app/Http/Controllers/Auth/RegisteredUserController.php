<?php

namespace Modules\Central\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\RegisterCentralUserRequest;
use App\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Show the form to create a new platform staff account.
     */
    public function create(): Response
    {
        return Inertia::render('Central/Auth/Register', [
            'roles' => Role::where('guard_name', 'central')->pluck('name'),
        ]);
    }

    /**
     * Create a new platform staff account.
     *
     * The authenticated admin performs this action on behalf of someone
     * else — the new account is not logged in automatically.
     */
    public function store(RegisterCentralUserRequest $request): RedirectResponse
    {
        $user = CentralUser::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        $user->assignRole($request->string('role')->toString());

        event(new Registered($user));

        return redirect()->route('central.dashboard')->with('status', 'staff-created');
    }
}
