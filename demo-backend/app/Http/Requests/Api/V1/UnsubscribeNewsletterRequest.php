<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UnsubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email')
                ? Str::lower(trim((string) $this->input('email')))
                : null,
            'token' => trim((string) $this->input('token')),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'required_without:token', 'string', 'email', 'max:191'],
            'token' => ['nullable', 'required_without:email', 'string', 'max:64'],
        ];
    }
}
