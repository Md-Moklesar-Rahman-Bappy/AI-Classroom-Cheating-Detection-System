<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasRole('system_admin')) {
            abort(403);
        }
        $users = User::with('roles')->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        if (! auth()->user()->hasRole('system_admin')) {
            abort(403);
        }
        $roles = Role::all();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasRole('system_admin')) {
            abort(403);
        }
        $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users', 'password' => ['required', Password::min(8)->letters()->numbers()->symbols()], 'roles' => 'required|array', 'roles.*' => 'exists:roles,id']);
        $user = User::create(['name' => $request->name, 'email' => $request->email, 'password' => Hash::make($request->password)]);
        $user->roles()->sync($request->roles);
        AuditHelper::log('user_created', 'user', (string) $user->id);

        return redirect()->route('users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        if (! auth()->user()->hasRole('system_admin')) {
            abort(403);
        }
        $roles = Role::all();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if (! auth()->user()->hasRole('system_admin')) {
            abort(403);
        }
        $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email,'.$user->id, 'roles' => 'required|array']);
        $user->update($request->only(['name', 'email']));
        $user->roles()->sync($request->roles);
        AuditHelper::log('user_updated', 'user', (string) $user->id);

        return redirect()->route('users.index')->with('success', 'Updated');
    }

    public function destroy(User $user)
    {
        if (! auth()->user()->hasRole('system_admin')) {
            abort(403);
        }
        if ($user->hasRole('system_admin')) {
            $adminCount = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->count();
            if ($adminCount <= 1) {
                AuditHelper::log('user.delete_blocked', 'user', (string) $user->id, 'failure', ['reason' => 'last_system_admin']);

                return redirect()->route('users.index')->withErrors(['user' => 'Cannot delete the last active System Administrator. Assign another administrator first.']);
            }
            if ($user->id === auth()->id() && $adminCount <= 1) {
                return redirect()->route('users.index')->withErrors(['user' => 'You cannot delete your own account as the last System Administrator.']);
            }
        }
        $user->delete();
        AuditHelper::log('user_deleted', 'user', (string) $user->id);

        return redirect()->route('users.index')->with('success', 'Deleted');
    }
}
