<?php

namespace App\Http\Requests\Admin\Assistant;

use Illuminate\Foundation\Http\FormRequest;

class AssistantCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function payload(): array
    {
        $data = $this->validated();
        $data['name'] = trim(strip_tags($data['name']));
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }
}
