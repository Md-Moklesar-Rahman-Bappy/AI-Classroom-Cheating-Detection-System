<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->hasRole('system_admin')) {
            $adminCount = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->count();
            if ($adminCount <= 1) {
                AuditHelper::log('profile.delete_blocked', 'user', (string) $user->id, 'failure', ['reason' => 'last_system_admin']);

                return Redirect::route('profile.edit')->withErrors(['password' => 'Cannot delete the last active System Administrator account. Assign another administrator first.'], 'userDeletion');
            }
        }

        AuditHelper::log('profile.delete', 'user', (string) $user->id, 'success', ['email' => $user->email]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
