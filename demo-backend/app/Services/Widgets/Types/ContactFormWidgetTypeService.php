<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class ContactFormWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'contact_form';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        // Non-translatable display options
        $config['layout']       = (string) $request->input('config.layout', 'card');
        $config['show_phone']   = $request->boolean('config.show_phone', false);
        $config['show_subject'] = $request->boolean('config.show_subject', true);

        // Translatable fields stored on the default config
        $config['title']           = trim((string) $request->input('config.title', ''));
        $config['subtitle']        = trim((string) $request->input('config.subtitle', ''));
        $config['submit_label']    = trim((string) $request->input('config.submit_label', 'Send Message'));
        $config['success_message'] = trim((string) $request->input('config.success_message', 'Your message has been sent. We will get back to you shortly.'));
        $config['fixed_subject']   = trim((string) $request->input('config.fixed_subject', ''));

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $config = [];

        foreach (['title', 'subtitle', 'submit_label', 'success_message', 'fixed_subject'] as $key) {
            $value = trim((string) ($translation[$key] ?? ''));
            if ($value !== '') {
                $config[$key] = $value;
            }
        }

        return $config;
    }
}
