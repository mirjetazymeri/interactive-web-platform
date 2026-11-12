<?php

namespace App\Http\Requests\Admin\Widget;

use App\Services\Widgets\Types\FooterCtaWidgetTypeService as FooterCta;

class FooterCtaRules
{
    public static function rules(): array
    {
        $rules = [
            'config.heading' => ['required', 'string', 'max:500'],
            // A page path ("/contact") or an absolute http(s), mailto: or tel: link.
            'config.button_link' => ['nullable', 'string', 'max:1000', 'regex:/^(\/[^\s\/\\\\]|\/$|#|https?:\/\/|mailto:|tel:)/i', 'required_with:config.button_label'],
            'config.show_email' => ['nullable', 'boolean'],
        ];

        foreach (FooterCta::TEXT_FIELDS as $field) {
            $max = in_array($field, FooterCta::LONG_FIELDS, true) ? 500 : 255;
            $rules["config.$field"] ??= ['nullable', 'string', "max:$max"];
            $rules["translations.*.$field"] = ['nullable', 'string', "max:$max"];
        }

        return $rules;
    }
}
