<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Text that ends up in <title>, meta tags and structured data: no HTML tags and
 * no control characters (line breaks are allowed). The website escapes it as
 * well; this keeps markup out of the stored value.
 */
class PlainText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }
        if (! is_string($value) || preg_match('/<\s*\/?\s*[a-z!?]/i', $value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            $fail('The :attribute must be plain text without HTML.');
        }
    }
}
