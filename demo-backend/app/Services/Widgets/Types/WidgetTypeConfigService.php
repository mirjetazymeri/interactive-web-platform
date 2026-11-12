<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

interface WidgetTypeConfigService
{
    public function type(): string;

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array;

    public function extractTranslationConfig(array $translation): array;
}
