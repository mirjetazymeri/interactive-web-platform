<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.permission:role_view')->only(['index']);
        $this->middleware('check.permission:role_create')->only(['create']);
        $this->middleware('check.permission:role_edit')->only(['edit']);
        $this->middleware('check.permission:role_delete')->only(['delete']);
    }

    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|unique:roles,name',
            ]);

            Role::create(['name' => $request->name]);

            return redirect()->route('admin.roles.index')->with('success', 'Role created.');
        }

        return view('admin.roles.create');
    }

    public function edit(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|unique:roles,name,' . $role->id,
            ]);

            $role->update(['name' => $request->name]);

            return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
        }

        return view('admin.roles.edit', compact('role'));
    }

    public function delete($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
