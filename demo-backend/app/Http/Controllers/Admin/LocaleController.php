<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LocaleController extends Controller
{
    public function index()
    {
        $locales = Locale::orderByDesc('is_default')
            ->orderBy('code')
            ->get();

        return view('admin.locales.index', compact('locales'));
    }

    public function create()
    {
        return view('admin.locales.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:locales,code',
            'name' => 'required|string|max:255',
            'flag' => 'nullable|string|max:255',
        ]);

        $data['code'] = strtolower(str_replace('_', '-', $data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        Locale::create($data);

        Cache::forget('locales.active');

        return redirect()
            ->route('admin.locales.index')
            ->with('success', 'Locale created successfully.');
    }

    public function edit(Locale $locale)
    {
        return view('admin.locales.edit', compact('locale'));
    }

    public function update(Request $request, Locale $locale)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:locales,code,' . $locale->id,
            'name' => 'required|string|max:255',
            'flag' => 'nullable|string|max:255',
        ]);

        $data['code'] = strtolower(str_replace('_', '-', $data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        $locale->update($data);

        Cache::forget('locales.active');

        return redirect()
            ->route('admin.locales.index')
            ->with('success', 'Locale updated successfully.');
    }

    public function destroy(Locale $locale)
    {
        if ($locale->is_default) {
            return back()->with('error', 'Cannot delete default locale.');
        }

        $locale->delete();

        Cache::forget('locales.active');

        return back()->with('success', 'Locale deleted.');
    }

    public function switch($locale)
    {
        $locale = strtolower(str_replace('_', '-', $locale));

        $locales = config('app.supported_locales', []);

        if (!in_array($locale, $locales, true)) {
            abort(400, 'Invalid language.');
        }

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        } else {
            session()->put('locale', $locale);
        }

        return back();
    }
}
