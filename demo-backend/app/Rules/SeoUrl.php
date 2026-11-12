<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A canonical URL override: a site path ("/services/web") that the website
 * localizes, or an absolute http(s) URL. Never a protocol-relative URL, a
 * script/data scheme, credentials or control characters.
 */
class SeoUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }
        if (! is_string($value) || strlen($value) > 2048 || preg_match('/[\x00-\x20\\\\<>"\']/', $value)) {
            $fail('The :attribute must be a site path such as /services or a full https:// address.');

            return;
        }
        if (str_starts_with($value, '/')) {
            if (str_starts_with($value, '//')) {
                $fail('The :attribute must be a site path such as /services or a full https:// address.');
            }

            return;
        }
        $parts = parse_url($value);
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            $fail('The :attribute must be a site path such as /services or a full https:// address.');
        }
    }
}
