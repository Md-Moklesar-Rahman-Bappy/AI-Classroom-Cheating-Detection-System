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
    public function index(Request $request)
    {
        if (! auth()->user()->hasRole('system_admin')) {
            abort(403);
        }
        $roles = Role::all();
        $query = User::with('roles');
        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            });
        }
        $sort = $request->get('sort', 'name');
        $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        if (in_array($sort, ['name', 'email', 'created_at'])) {
            $query->orderBy($sort, $dir);
        }
        $users = $query->paginate(10)->withQueryString();

        return view('users.index', compact('users', 'roles'));
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
        $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users', 'password' => ['required', Password::min(8)->letters()->numbers()->symbols(), 'confirmed'], 'roles' => 'required|array', 'roles.*' => 'exists:roles,id']);
        $user = User::create(['name' => $request->name, 'email' => $request->email, 'password' => Hash::make($request->password)]);
        $user->roles()->sync($request->roles);
        $roleNames = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
        AuditHelper::log('user_created', 'user', (string) $user->id, 'success', ['roles' => $roleNames, 'actor' => auth()->id()]);

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
        $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email,'.$user->id, 'roles' => 'required|array', 'roles.*' => 'exists:roles,id']);
        $oldRoles = $user->roles->pluck('name')->toArray();
        $newRoles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
        if ($user->id === auth()->id() && in_array('system_admin', $oldRoles) && ! in_array('system_admin', $newRoles)) {
            $adminCount = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->count();
            if ($adminCount <= 1) {
                return redirect()->route('users.edit', $user)->withErrors(['roles' => 'You cannot remove your own System Administrator role as the last administrator.']);
            }
        }
        if (in_array('system_admin', $oldRoles) && ! in_array('system_admin', $newRoles)) {
            $adminCount = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->count();
            if ($adminCount <= 1) {
                return redirect()->route('users.edit', $user)->withErrors(['roles' => 'Cannot remove System Administrator from the last admin account.']);
            }
        }
        $user->update($request->only(['name', 'email']));
        $user->roles()->sync($request->roles);
        AuditHelper::log('role_updated', 'user', (string) $user->id, 'success', ['old_roles' => $oldRoles, 'new_roles' => $newRoles, 'actor' => auth()->id()]);

        return redirect()->route('users.index')->with('success', 'Updated role from '.implode(',', $oldRoles).' to '.implode(',', $newRoles));
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
        $id = $user->id;
        $user->delete();
        AuditHelper::log('user_deleted', 'user', (string) $id);

        return redirect()->route('users.index')->with('success', 'Deleted (soft)');
    }

    public function restore($id)
    {
        if (! auth()->user()->hasRole('system_admin')) abort(403);
        $u = User::onlyTrashed()->findOrFail($id);
        $u->restore();
        AuditHelper::log('user_restored', 'user', (string) $id);
        return back()->with('success', 'User restored');
    }
}
