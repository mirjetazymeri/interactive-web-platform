<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Locale;
use Illuminate\Support\Facades\Cache;

class LocaleController extends Controller
{
    protected string $cacheKey = 'api.locales.v1';

    public function index()
    {
        $data = Cache::rememberForever($this->cacheKey, function () {

            $locales = Locale::where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->get();

            return [
                'locales' => $locales->map(fn ($l) => [
                    'code' => $l->code,
                    'name' => $l->name,
                    'flag' => $l->flag,
                    'is_default' => (bool) $l->is_default,
                ])->values(),

                'default' => optional($locales->firstWhere('is_default', true))->code,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                ...$data,
                'current' => app()->getLocale(), // 🔥 from middleware
            ]
        ]);
    }
}
