<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Module;

class PermissionController extends Controller
{

    public function __construct()
    {
        $this->middleware('check.permission:permission_view')->only(['index']);
        $this->middleware('check.permission:permission_create')->only(['create']);
        $this->middleware('check.permission:permission_edit')->only(['edit']);
        $this->middleware('check.permission:permission_delete')->only(['destroy']);
    }

    public function index()
    {
        $permissions = Permission::with('module')->latest()->get();
        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        $modules = Module::orderBy('name')->get();
        return view('admin.permissions.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
            'module_id' => 'required|exists:modules,id',
        ]);

        Permission::create([
            'name' => $request->name,
            'module_id' => $request->module_id,
            'guard_name' => 'web',
        ]);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Permission created.');
    }

    public function edit(Permission $permission)
    {
        $modules = Module::orderBy('name')->get();
        return view('admin.permissions.edit', compact('permission', 'modules'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
            'module_id' => 'required|exists:modules,id',
        ]);

            $permission->update([
                'name' => $request->name,
                'module_id' => $request->module_id,
                'guard_name' => 'web',
            ]);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Permission updated.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Permission deleted.');
    }
}
