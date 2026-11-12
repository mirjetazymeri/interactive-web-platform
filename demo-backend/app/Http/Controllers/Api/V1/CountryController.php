<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    public function index(): JsonResponse
    {
        $countries = Country::query()
            ->orderBy('id')
            ->get(['code', 'name'])
            ->map(fn (Country $country) => [
                'code' => strtoupper((string) $country->code),
                'name' => (string) $country->name,
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => $countries,
        ]);
    }

    public function cities(string $code): JsonResponse
    {
        $country = Country::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        $cities = $country->cities()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($city) => [
                'id' => $city->id,
                'name' => $city->name,
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'country' => [
                    'code' => strtoupper((string) $country->code),
                    'name' => (string) $country->name,
                ],
                'cities' => $cities,
            ],
        ]);
    }
}
