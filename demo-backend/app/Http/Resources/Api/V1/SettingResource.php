<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $schema = config('settings');

        return $this->buildTree($schema, '');
    }

    protected function buildTree(array $schema, string $prefix): array
    {
        $output = [];

        foreach ($schema as $key => $value) {

            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            // ✅ FIELD
            if (isset($value['type'])) {

                // Skip private fields
                if (empty($value['public'])) {
                    continue;
                }

                $val = $this[$fullKey] ?? ($value['default'] ?? null);

                // File handling
                if (($value['type'] ?? null) === 'file' && $val) {
                    $val = asset('storage/' . $val);
                }

                // Structured values (e.g. per-language SEO defaults) are stored as JSON text.
                if (($value['type'] ?? null) === 'json') {
                    $decoded = is_string($val) ? json_decode($val, true) : $val;
                    $val = is_array($decoded) && $decoded !== [] ? $decoded : null;
                }

                $output[$key] = $val;

                continue;
            }

            // ✅ NESTED GROUP
            if (is_array($value)) {

                $child = $this->buildTree($value, $fullKey);

                if (!empty($child)) {
                    $output[$key] = $child;
                }
            }
        }

        return $output;
    }
}
