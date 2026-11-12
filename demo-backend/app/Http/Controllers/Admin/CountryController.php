<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));

        $countries = Country::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->withCount('cities')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.countries.index', compact('countries', 'q'));
    }

    public function create()
    {
        return view('admin.countries.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateCountry($request);

        Country::create($data);

        return redirect()->route('admin.countries.index')->with('success', 'Country created.');
    }

    public function edit(int $id)
    {
        $country = Country::with(['cities' => fn ($q) => $q->orderBy('name')])->findOrFail($id);

        return view('admin.countries.edit', compact('country'));
    }

    public function update(Request $request, int $id)
    {
        $country = Country::findOrFail($id);
        $data = $this->validateCountry($request, $country->id);

        $country->update($data);

        return back()->with('success', 'Country updated.');
    }

    public function delete(int $id)
    {
        Country::findOrFail($id)->delete();

        return redirect()->route('admin.countries.index')->with('success', 'Country deleted.');
    }

    public function storeCity(Request $request, int $countryId)
    {
        $country = Country::findOrFail($countryId);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('cities', 'name')->where(fn ($q) => $q->where('country_id', $country->id)),
            ],
        ]);

        $country->cities()->create([
            'name' => trim((string) $validated['name']),
        ]);

        return back()->with('success', 'City added.');
    }

    public function updateCity(Request $request, int $countryId, int $cityId)
    {
        $country = Country::findOrFail($countryId);
        $city = City::query()->where('country_id', $country->id)->findOrFail($cityId);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('cities', 'name')
                    ->ignore($city->id)
                    ->where(fn ($q) => $q->where('country_id', $country->id)),
            ],
        ]);

        $city->update([
            'name' => trim((string) $validated['name']),
        ]);

        return back()->with('success', 'City updated.');
    }

    public function deleteCity(int $countryId, int $cityId)
    {
        $country = Country::findOrFail($countryId);
        $city = City::query()->where('country_id', $country->id)->findOrFail($cityId);

        $city->delete();

        return back()->with('success', 'City deleted.');
    }

    protected function validateCountry(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'size:2',
                Rule::unique('countries', 'code')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $data['code'] = Str::upper(trim((string) $data['code']));
        $data['name'] = trim((string) $data['name']);

        return $data;
    }
}
