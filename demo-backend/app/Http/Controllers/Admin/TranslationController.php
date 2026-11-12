<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index()
    {
        $locales = array_keys(config('locales'));
        $translations = [];

        // Load translations for each locale
        foreach ($locales as $locale) {
            $path = lang_path("$locale/translations.php");
            $translations[$locale] = file_exists($path) ? include $path : [];
        }

        // ✅ Collect all unique keys from all languages, sorted
        $allKeys = collect($translations)
            ->map(fn($items) => array_keys($items))
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('admin.translations.index', compact('locales', 'translations', 'allKeys'));
    }

    public function update(Request $request)
    {
        $key = $request->key;
        $values = $request->translations; // ['en' => 'Hello', 'fr' => 'Bonjour']

        foreach ($values as $locale => $text) {
            $path = lang_path("$locale/translations.php");
            $langArray = file_exists($path) ? include $path : [];
            $langArray[$key] = $text;
            file_put_contents($path, "<?php\n\nreturn " . var_export($langArray, true) . ";\n");
        }

        return back()->with('success', 'Translation updated.');
    }

    public function store(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        foreach ($request->translations as $locale => $text) {
            $path = lang_path("$locale/translations.php");
            $langArray = file_exists($path) ? include $path : [];
            $langArray[$request->key] = $text;
            file_put_contents($path, "<?php\n\nreturn " . var_export($langArray, true) . ";\n");
        }

        return back()->with('success', 'Translation added.');
    }

    public function destroy($key)
    {
        $locales = array_keys(config('locales'));

        foreach ($locales as $locale) {
            $path = lang_path("$locale/translations.php");
            if (file_exists($path)) {
                $langArray = include $path;
                unset($langArray[$key]);
                file_put_contents($path, "<?php\n\nreturn " . var_export($langArray, true) . ";\n");
            }
        }

        return back()->with('success', 'Translation deleted.');
    }
}
