<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'    => trim((string) $this->input('name')),
            'email'   => trim((string) $this->input('email')),
            'subject' => trim((string) $this->input('subject')),
            'message' => trim((string) $this->input('message')),
            'phone'   => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email:rfc', 'max:191'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'phone'   => ['nullable', 'string', 'max:30'],
        ];
    }
}
