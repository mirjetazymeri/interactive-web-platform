<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{

    public function __construct()
    {
        $this->middleware('check.permission:module_view')->only(['index']);
        $this->middleware('check.permission:module_create')->only(['create']);
        $this->middleware('check.permission:module_edit')->only(['edit']);
        $this->middleware('check.permission:module_delete')->only(['destroy']);
    }

    public function index()
    {
        $modules = Module::latest()->get();
        return view('admin.modules.index', compact('modules'));
    }

    public function create()
    {
        return view('admin.modules.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:modules,name',
        ]);

        Module::create($request->only('name', 'description'));

        return redirect()->route('admin.modules.index')
                         ->with('success', 'Module created.');
    }

    public function edit(Module $module)
    {
        return view('admin.modules.edit', compact('module'));
    }

    public function update(Request $request, Module $module)
    {
        $request->validate([
            'name' => 'required|unique:modules,name,' . $module->id,
        ]);

        $module->update($request->only('name', 'description'));

        return redirect()->route('admin.modules.index')
                         ->with('success', 'Module updated.');
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return redirect()->route('admin.modules.index')
                         ->with('success', 'Module deleted.');
    }
}
