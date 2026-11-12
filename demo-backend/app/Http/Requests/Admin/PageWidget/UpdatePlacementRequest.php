<?php

namespace App\Http\Requests\Admin\PageWidget;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Admin role and authentication are enforced by the route group.
    }

    public function rules(): array
    {
        return [
            'config' => ['required', 'array:display_mode'],
            'config.display_mode' => ['required', 'in:slider,stacked'],
        ];
    }
}
