<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLevel;
use App\Models\Permission;
use Illuminate\Http\Request;

class AccessLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.permission:access_level_view')->only(['index']);
        $this->middleware('check.permission:access_level_create')->only(['create']);
        $this->middleware('check.permission:access_level_edit')->only(['edit']);
        $this->middleware('check.permission:access_level_delete')->only(['destroy']);
    }

    public function index()
    {
        $accessLevels = AccessLevel::with('permissions')->latest()->get();
        return view('admin.access_levels.index', compact('accessLevels'));
    }

    public function create()
    {
        $permissions = Permission::with('module')->orderBy('name')->get();
        return view('admin.access_levels.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:access_levels,name',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $accessLevel = AccessLevel::create($request->only('name', 'description'));

        if ($request->has('permissions')) {
            $accessLevel->permissions()->attach($request->permissions);
        }

        return redirect()->route('admin.access_levels.index')
                         ->with('success', 'Access level created.');
    }

    public function edit(AccessLevel $accessLevel)
    {
        $permissions = Permission::with('module')->orderBy('name')->get();
        $accessLevel->load('permissions');

        return view('admin.access_levels.edit', compact('accessLevel', 'permissions'));
    }

    public function update(Request $request, AccessLevel $accessLevel)
    {
        $request->validate([
            'name' => 'required|unique:access_levels,name,' . $accessLevel->id,
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $accessLevel->update($request->only('name', 'description'));

        $accessLevel->permissions()->sync($request->permissions ?? []);

        return redirect()->route('admin.access_levels.index')
                         ->with('success', 'Access level updated.');
    }

    public function destroy(AccessLevel $accessLevel)
    {
        $accessLevel->delete();
        return redirect()->route('admin.access_levels.index')
                         ->with('success', 'Access level deleted.');
    }
}
