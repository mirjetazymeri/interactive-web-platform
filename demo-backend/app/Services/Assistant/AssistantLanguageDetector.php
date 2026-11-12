<?php

namespace App\Services\Assistant;

/**
 * Which language a question is written in, so the answer can follow the visitor rather than the
 * page they are on. Deterministic and local: words only one language uses (config), Albanian
 * letters (ë, ç), and words only one language's admin content uses. Returns null when unsure;
 * the caller then keeps the conversation's language or the site's.
 */
class AssistantLanguageDetector
{
    public function __construct(private AssistantNormalizer $normalizer) {}

    /**
     * @param  array<string, array<string, true>>  $lexicons  language → folded words of its admin content
     * @return array{language: ?string, scores: array<string, float>}
     */
    public function detect(string $message, array $lexicons = []): array
    {
        $config = config('assistant.language');
        $words = $this->normalizer->words($message);
        $scores = array_fill_keys(array_keys($config['markers'] ?? []), 0.0);
        if ($words === [] || $scores === []) {
            return ['language' => null, 'scores' => $scores];
        }

        $markers = array_map(fn ($list) => array_fill_keys($list, true), $config['markers']);
        foreach ($words as $position => $word) {
            // "do të" is Albanian ("will"), not "do you".
            if ($word === 'do' && ($words[$position + 1] ?? null) === 'te') {
                continue;
            }
            foreach ($markers as $language => $set) {
                if (isset($set[$word])) {
                    $scores[$language] += 1;
                }
            }
            if (mb_strlen($word) >= 4) {
                $owners = array_keys(array_filter($lexicons, fn ($lexicon) => isset($lexicon[$word])));
                if (count($owners) === 1 && isset($scores[$owners[0]])) {
                    $scores[$owners[0]] += 0.5;
                }
            }
        }
        foreach ($config['strong'] ?? [] as $language => $list) {
            if (array_intersect($words, $list) !== [] && isset($scores[$language])) {
                $scores[$language] += (float) ($config['min_score'] ?? 2);
            }
        }
        foreach ($config['leading'] ?? [] as $language => $list) {
            if (count($words) > 1 && in_array($words[0], $list, true) && isset($scores[$language])) {
                $scores[$language] += 1;
            }
        }
        // Albanian letters survive only in the raw text.
        if (isset($scores['sq']) && preg_match('/[ëçËÇ]/u', $message)) {
            $scores['sq'] += 2;
        }

        arsort($scores);
        [$best, $second] = array_pad(array_values($scores), 2, 0.0);
        $language = array_key_first($scores);
        $sure = $best >= (float) ($config['min_score'] ?? 2) && $best >= (float) ($config['lead'] ?? 1.5) * $second && $best > $second;

        return ['language' => $sure ? $language : null, 'scores' => array_map(fn ($s) => round($s, 2), $scores)];
    }
}
