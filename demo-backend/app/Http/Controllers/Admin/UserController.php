<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AccessLevel;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\Channel;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'accessLevels'])->get();
        return view('admin.users.index', compact('users'));
    }

    public function create(Request $request)
    {
        $channels = Channel::all();
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'roles' => 'array',
                'access_levels' => 'array',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            if ($request->filled('roles')) {
                $roles = Role::whereIn('id', $request->roles)->get();
                $user->syncRoles($roles);
            }

            if ($request->filled('access_levels')) {
                $user->accessLevels()->sync($request->access_levels);
            }

            if ($request->filled('channels')) {
                $user->channels()->sync($request->channels);
            }

            return redirect()->route('admin.users.index')->with('success', 'User created.');
        }

        $roles = Role::all();
        $accessLevels = AccessLevel::all();
        return view('admin.users.create', compact('roles', 'accessLevels', 'channels'));
    }

    public function edit(Request $request, $id)
    {
        $user = User::with(['roles', 'accessLevels', 'channels'])->findOrFail($id);
        $channels = Channel::all();

        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6|confirmed',
                'roles' => 'array',
                'access_levels' => 'array',
            ]);

            $user->name = $request->name;
            $user->email = $request->email;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            $roles = Role::whereIn('id', $request->input('roles', []))->get();
            $user->syncRoles($roles);

            if ($request->filled('access_levels')) {
                $user->accessLevels()->sync($request->access_levels);
            } else {
                $user->accessLevels()->detach();
            }

            if ($request->filled('channels')) {
                $user->channels()->sync($request->channels);
            } else {
                $user->channels()->detach();
            }

            return redirect()->route('admin.users.index')->with('success', 'User updated.');
        }

        $roles = Role::all();
        $assignedRoles = $user->roles->pluck('id')->toArray();

        $accessLevels = AccessLevel::all();
        $assignedAccessLevels = $user->accessLevels->pluck('id')->toArray();
        $assignedChannels = $user->channels->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'assignedRoles', 'accessLevels', 'assignedAccessLevels', 'channels', 'assignedChannels'));
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function disableTwoFactor($id)
    {
        $user = User::findOrFail($id);

        $user->forceFill([
            'two_factor_secret'       => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('admin.users.edit', $id)
            ->with('success', 'Two-factor authentication has been disabled for ' . $user->name . '.');
    }
}
