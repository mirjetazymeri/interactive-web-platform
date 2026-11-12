<?php

namespace App\Services\Assistant;

/**
 * Turns visitor questions and admin wording into comparable tokens: lower
 * case, diacritics folded (ë → e, ç → c), punctuation removed, stopwords
 * dropped and light per-language suffix stripping. Admin content and visitor
 * text always go through the same steps, so both sides meet.
 */
class AssistantNormalizer
{
    /** Lower-cased text without diacritics or punctuation, single-spaced. */
    public function fold(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        // English contractions: "what's" → "what is", "don't" → "do not". Albanian "t’ju", "s’ka" stay as they are.
        $text = preg_replace(["/\bcan['’]t\b/u", "/\bwon['’]t\b/u"], ['cannot', 'will not'], $text) ?? $text;
        $text = preg_replace(["/(\p{L})n['’]t\b/u", "/(\p{L})['’]s\b/u", "/(\p{L})['’]re\b/u", "/(\p{L})['’]m\b/u", "/(\p{L})['’]ll\b/u", "/(\p{L})['’]ve\b/u", "/(\p{L})['’]d\b/u"],
            ['$1 not', '$1 is', '$1 are', '$1 am', '$1 will', '$1 have', '$1 would'], $text) ?? $text;
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KD) ?: $text;
        }
        $text = preg_replace('/\p{Mn}+/u', '', $text) ?? $text;
        // "e-commerce", "e-shop": a one-letter prefix belongs to the word.
        $text = preg_replace('/(?<!\p{L})(\p{L})-(?=\p{L})/u', '$1', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /** Folded words, in order, without punctuation. */
    public function words(string $text): array
    {
        $folded = $this->fold($text);

        return $folded === '' ? [] : explode(' ', $folded);
    }

    /**
     * Content tokens (stems) in order, without stopwords or single letters.
     *
     * @param  array<string, true>  $extraStopwords  folded words to ignore as well (e.g. the company name)
     */
    public function tokens(string $text, string $locale, array $extraStopwords = []): array
    {
        return $this->tokenize($text, $locale, $extraStopwords)[0];
    }

    /**
     * Content tokens and, for each, the index of the word it came from, so a token can be placed
     * in its clause ("not marketing, an ERP system").
     *
     * @return array{0: list<string>, 1: list<int>}
     */
    public function tokenize(string $text, string $locale, array $extraStopwords = []): array
    {
        $stopwords = $this->stopwords($locale) + $extraStopwords;
        $tokens = $origins = [];
        foreach ($this->words($text) as $index => $word) {
            if (isset($stopwords[$word]) || (mb_strlen($word) < 2 && ! ctype_digit($word))) {
                continue;
            }
            $stem = $this->stem($word, $locale);
            // "needs", "wants": a form of a stopword is one too.
            if (isset($stopwords[$stem])) {
                continue;
            }
            $tokens[] = $stem;
            $origins[] = $index;
        }

        return [$tokens, $origins];
    }

    /** Stopwords for a language; other languages also ignore English filler, since visitors mix it in. */
    public function stopwords(string $locale): array
    {
        $lists = config('assistant.stopwords', []);
        $base = $this->language($locale);
        $words = $lists[$base] ?? [];
        if ($base !== 'en') {
            $words = [...$words, ...($lists['en'] ?? [])];
        }

        return array_fill_keys($words, true);
    }

    public function stem(string $word, string $locale): string
    {
        if (ctype_digit($word)) {
            return $word;
        }

        return $this->language($locale) === 'en' ? $this->stemEnglish($word) : $this->stemBySuffix($word, $locale);
    }

    /**
     * How many items the visitor asked for: "show me two projects" → 2, "all services" → 'all'.
     * English quantity words are understood in every language, as visitors mix them in.
     */
    public function quantity(string $text, string $locale): int|string|null
    {
        $lists = config('assistant.quantities', []);
        $words = [...($lists[$this->language($locale)] ?? []), ...($lists['en'] ?? [])];
        foreach ($this->words($text) as $word) {
            if (isset($words[$word])) {
                return $words[$word];
            }
            if (preg_match('/^[1-9]$/', $word)) {
                return (int) $word;
            }
        }

        return null;
    }

    /** Whether a folded text is one of the "tell me more" replies for the locale. */
    public function isContinuation(string $folded, string $locale): bool
    {
        $phrases = config('assistant.continue_phrases', []);
        $list = $phrases[$this->language($locale)] ?? $phrases['en'] ?? [];

        return in_array($folded, $list, true);
    }

    public function language(string $locale): string
    {
        $base = strtolower(strtok($locale, '-_') ?: $locale);

        // The CMS has used both "al" and "sq" for Albanian.
        return $base === 'al' ? 'sq' : $base;
    }

    private function stemEnglish(string $word): string
    {
        $length = strlen($word);
        if ($length > 4 && str_ends_with($word, 'ies')) {
            return substr($word, 0, -3).'y';
        }
        if (str_ends_with($word, 'sses')) {
            return substr($word, 0, -2);
        }
        if ($length > 4 && preg_match('/(x|ch|sh)es$/', $word)) {
            return substr($word, 0, -2);
        }
        if ($length > 3 && str_ends_with($word, 's') && ! preg_match('/(ss|us|is)$/', $word)) {
            $word = substr($word, 0, -1);
            $length--;
        }
        if ($length > 6 && str_ends_with($word, 'ing')) {
            return substr($word, 0, -3);
        }
        if ($length > 5 && str_ends_with($word, 'ed')) {
            return substr($word, 0, -2);
        }

        return $word;
    }

    private function stemBySuffix(string $word, string $locale): string
    {
        $minimum = (int) config('assistant.min_stem', 4);
        foreach (config('assistant.suffixes.'.$this->language($locale), []) as $suffix) {
            if (str_ends_with($word, $suffix) && strlen($word) - strlen($suffix) >= $minimum) {
                return substr($word, 0, -strlen($suffix));
            }
        }

        return $word;
    }
}
