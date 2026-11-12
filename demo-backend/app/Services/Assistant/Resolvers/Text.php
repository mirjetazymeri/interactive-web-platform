<?php

namespace App\Services\Assistant\Resolvers;

use Illuminate\Support\Str;

/** Plain, length-limited text for assistant answers and cards. Never HTML. */
final class Text
{
    public static function plain(?string $value, int $limit = 400): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Keep paragraph breaks; collapse other whitespace and drop control characters.
        $text = preg_replace('/[^\P{C}\n]+/u', ' ', str_replace(["\r\n", "\r"], "\n", $text)) ?? '';
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s*\n\s*(\n\s*)+/', "\n\n", $text) ?? '');

        return Str::limit($text, $limit, '…');
    }
}
