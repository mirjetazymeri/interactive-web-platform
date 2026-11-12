<?php

namespace App\Http\Requests\Admin\Widget;

class ServiceXRules
{
    public static function rules(): array
    {
        $id = ['required', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', 'distinct:strict'];
        $rules = [
            'config.display_mode' => ['prohibited'],
            'config.section_label' => ['nullable', 'string', 'max:255'],
            'config.section_index' => ['nullable', 'string', 'max:255'],
            'config.services' => ['nullable', 'array', 'list', 'max:100'],
            'config.services.*' => ['array'],
            'config.services.*.id' => $id,
            'config.services.*.name' => ['required', 'string', 'max:255'],
            'config.services.*.title' => ['required', 'string', 'max:255'],
            'config.services.*.subtitle' => ['nullable', 'string', 'max:10000'],
            'config.services.*.image_alt' => ['nullable', 'string', 'max:255'],
            'config.services.*.image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'config.services.*.remove_image' => ['sometimes', 'boolean'],
            'config.services.*.position' => ['sometimes', 'integer', 'min:0'],
            'config.services.*.active' => ['sometimes', 'boolean'],
            'config.services.*.subservices' => ['nullable', 'array', 'list', 'max:100'],
            'config.services.*.subservices.*' => ['array'],
            'config.services.*.subservices.*.id' => $id,
            'config.services.*.subservices.*.title' => ['required', 'string', 'max:255'],
            'config.services.*.subservices.*.description' => ['nullable', 'string', 'max:10000'],
            'config.services.*.subservices.*.position' => ['sometimes', 'integer', 'min:0'],
            'config.services.*.subservices.*.active' => ['sometimes', 'boolean'],
            'translations.*.section_index' => ['nullable', 'string', 'max:255'],
            'translations.*.services' => ['nullable', 'array', 'max:100'],
            'translations.*.services.*' => ['array:name,title,subtitle,image_alt,subservices'],
            'translations.*.services.*.subservices' => ['nullable', 'array', 'max:100'],
            'translations.*.services.*.subservices.*' => ['array:title,description'],
        ];
        foreach (['name', 'title', 'subtitle', 'image_alt'] as $field) {
            $rules["translations.*.services.*.$field"] = ['nullable', 'string', 'max:'.($field === 'subtitle' ? 10000 : 255)];
        }
        foreach (['title', 'description'] as $field) {
            $rules["translations.*.services.*.subservices.*.$field"] = ['nullable', 'string', 'max:'.($field === 'description' ? 10000 : 255)];
        }

        return $rules;
    }
}
