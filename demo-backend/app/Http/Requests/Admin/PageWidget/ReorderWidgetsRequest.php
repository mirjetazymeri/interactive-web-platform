<?php

namespace App\Http\Requests\Admin\PageWidget;

use Illuminate\Foundation\Http\FormRequest;

class ReorderWidgetsRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['ordering' => ['required','array','min:1'], 'ordering.*' => ['integer','exists:page_widgets,id']];
    }
}
