<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use DataTables;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Employee::query();

            return DataTables::eloquent($data)
                ->addIndexColumn()
                ->editColumn('salary', fn($row) => '$' . number_format($row->salary, 2))
                ->editColumn('hired_at', fn($row) => optional($row->hired_at)->format('Y-m-d'))
                ->addColumn('actions', function ($row) {
                    return view('admin.employees.actions', compact('row'))->render(); // Optional
                })
                ->rawColumns(['actions']) // Only if using HTML in actions
                ->toJson();
        }

        return view('admin.employees.index');
    }


    public function create()
    {
        $employee = new Employee();
        return view('admin.employees.create', compact('employee'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            if ($file->isValid()) {
                // Build safe file name
                $first = trim($data['first_name'] ?? '');
                $last  = trim($data['last_name'] ?? '');
                $base  = strtolower(preg_replace('/\s+/', '_', $first . '_' . $last));
                if ($base === '' || $base === '_') {
                    $base = 'employee';
                }

                $ext  = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
                $name = time() . '_' . $base . '.' . $ext;

                // Store in storage/app/public/uploads/avatars
                $path = $file->storeAs('uploads/avatars', $name, 'public');

                // Save relative path for DB
                $data['avatar_path'] = $path;
            }
        }

        $employee = Employee::create($data);

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Employee created.');
    }



    public function show(Employee $employee)
    {
        return view('admin.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('admin.employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validatedData($request, $employee->id);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            if ($file->isValid()) {
                // Delete old avatar if present
                if ($employee->avatar_path) {
                    Storage::disk('public')->delete($employee->avatar_path);
                }

                $first = trim($data['first_name'] ?? '');
                $last  = trim($data['last_name'] ?? '');
                $base  = strtolower(preg_replace('/\s+/', '_', $first . '_' . $last));
                if ($base === '' || $base === '_') {
                    $base = 'employee';
                }

                $ext  = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
                $name = time() . '_' . $base . '.' . $ext;

                $path = $file->storeAs('uploads/avatars', $name, 'public');

                $data['avatar_path'] = $path;
            }
        }

        $employee->update($data);

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }



    public function destroy(Employee $employee)
    {
        // delete avatar if exists
        if ($employee->avatar_path) {
            Storage::disk('public')->delete($employee->avatar_path);
        }

        $employee->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee deleted.');
    }

    protected function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'first_name' => ['required','string','max:100'],
            'last_name'  => ['required','string','max:100'],
            'email'      => [
                'required','email','max:255',
                Rule::unique('employees', 'email')->ignore($ignoreId),
            ],
            'phone'      => ['nullable','string','max:30'],
            'position'   => ['nullable','string','max:120'],
            'department' => ['nullable','string','max:120'],
            'hired_at'   => ['nullable','date'],
            'salary'     => ['required','numeric','min:0'],
            'status'     => ['required','string','in:active,inactive,terminated'],
            'avatar'     => ['nullable','image','max:2048'],
        ]);
    }
}
