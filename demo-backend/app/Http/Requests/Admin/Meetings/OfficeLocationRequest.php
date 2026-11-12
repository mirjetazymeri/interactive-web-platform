<?php

namespace App\Http\Requests\Admin\Meetings;

use Illuminate\Foundation\Http\FormRequest;

class OfficeLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'maps_url' => ['nullable', 'url:https', 'max:2048'],
            'arrival_instructions' => ['nullable', 'string', 'max:5000'],
            'is_default' => ['boolean'],
            'active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.arrival_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function payload(): array
    {
        $data = $this->validated();
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }
}
