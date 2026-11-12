<?php

namespace App\Http\Requests\Admin\PageWidget;

use Illuminate\Foundation\Http\FormRequest;

class AttachWidgetRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'widget_id' => ['required','integer','exists:widgets,id'],
            'config' => ['sometimes', 'array:display_mode'],
            'config.display_mode' => ['sometimes', 'required', 'in:slider,stacked'],
        ];
    }
}
