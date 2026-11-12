<?php

namespace App\Services\Assistant;

/**
 * What a question asks for, independent of its subject: pricing, a timeline, getting in touch,
 * something similar to a project, projects, information or a capability ("do you…"). Cue phrases
 * come from config('assistant.acts'), per language, matched as whole words in order. Stronger
 * requests come first, so "How much would a shop like Treforma cost?" is about pricing.
 */
class AssistantRequestClassifier
{
    public const ACTS = ['pricing', 'timeline', 'contact', 'similar', 'projects', 'information', 'capability'];

    public function __construct(private AssistantNormalizer $normalizer) {}

    /**
     * The question's clauses, word by word: which clause each word is in, whether that clause is
     * negative ("not marketing", "instead of 3D products", "nuk dua …") and how it joins the one
     * before ("and" adds a requirement, "or" offers an alternative). Punctuation ends a clause; a
     * negator starts a negative clause, an affirmation ("I need", "dua", "but") a positive one,
     * unless it directly follows the negator ("nuk dua marketing" stays negative). "And" keeps
     * the polarity ("not a website and hosting").
     *
     * @return array{words: list<array{clause: int, negative: bool, link: ?string}>, negation: bool, correction: bool, alternative: bool}
     */
    public function clauses(string $message, string $language): array
    {
        $config = config('assistant.clauses');
        $cues = $config[$language] ?? $config['en'];
        $segments = array_map(fn ($segment) => $this->normalizer->words($segment), preg_split('/[,;.!?:\n]+/u', $message) ?: []);
        // Folding a part of the text must give the same words as folding all of it; otherwise one segment.
        if (array_merge(...($segments ?: [[]])) !== $this->normalizer->words($message)) {
            $segments = [$this->normalizer->words($message)];
        }

        $out = [];
        $clause = -1;
        $negation = $alternative = false;
        // "Actually…", "I mean…", "instead", "not…": the visitor changes the topic.
        $correction = $this->contains(' '.implode(' ', array_merge(...($segments ?: [[]]))).' ', $cues['correction'] ?? []);
        foreach ($segments as $words) {
            if ($words === []) {
                continue;
            }
            $clause++;
            $negative = false;
            $link = null;
            $lastNegator = -9;
            for ($i = 0; $i < count($words); $i++) {
                $rest = ' '.implode(' ', array_slice($words, $i)).' ';
                $marker = null;
                foreach (['negators', 'affirm', 'and', 'or'] as $kind) {
                    foreach ($cues[$kind] ?? [] as $phrase) {
                        if (str_starts_with($rest, ' '.$phrase.' ') && (! $marker || substr_count($phrase, ' ') + 1 > $marker[1])) {
                            $marker = [$kind, substr_count($phrase, ' ') + 1];
                        }
                    }
                }
                if ($marker && ! ($marker[0] === 'affirm' && $i - $lastNegator <= 2) && $i > 0) {
                    $clause++;
                    $link = in_array($marker[0], ['and', 'or'], true) ? $marker[0] : null;
                    $negative = match ($marker[0]) {
                        'negators' => true, 'affirm' => false, default => $negative
                    };
                } elseif ($marker && $marker[0] === 'negators') {
                    $negative = true;
                }
                if ($marker && $marker[0] === 'negators') {
                    $lastNegator = $i;
                    $negation = true;
                }
                $alternative = $alternative || ($marker && $marker[0] === 'or');
                for ($j = 0; $j < ($marker[1] ?? 1); $j++) {
                    $out[] = ['clause' => $clause, 'negative' => $negative, 'link' => $link];
                }
                $i += ($marker[1] ?? 1) - 1;
            }
        }

        return ['words' => $out, 'negation' => $negation, 'correction' => $correction, 'alternative' => $alternative];
    }

    /**
     * @param  list<string>  $words  folded words of the question
     * @param  list<string>  $names  folded names of the projects and services it mentions ("treforma")
     * @return array{act: ?string, acts: list<string>, reference: bool, ellipsis: bool}
     */
    public function classify(array $words, string $language, array $names = []): array
    {
        $config = config('assistant.acts');
        $cues = $config[$language] ?? $config['en'];
        $text = ' '.implode(' ', $words).' ';

        $acts = [];
        foreach (self::ACTS as $act) {
            if ($this->contains($text, $cues[$act] ?? [])) {
                $acts[] = $act;
            }
        }
        // "like Treforma", "si Treforma": a comparison with something named in the question.
        if (! in_array('similar', $acts, true) && $this->compares($words, $language, $names, $config)) {
            $acts = array_values(array_intersect(self::ACTS, [...$acts, 'similar']));
        }

        $reference = array_intersect($words, $config['reference'][$language] ?? []) !== [];

        return [
            'act' => $acts[0] ?? null,
            'acts' => $acts,
            'reference' => $reference,
            // "What about mobile?": only the subject changes; the request carries over.
            'ellipsis' => $acts === [] && $this->starts($text, $cues['ellipsis'] ?? []),
        ];
    }

    private function contains(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_contains($text, ' '.$phrase.' ')) {
                return true;
            }
        }

        return false;
    }

    private function starts(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_starts_with($text, ' '.$phrase.' ')) {
                return true;
            }
        }

        return false;
    }

    private function compares(array $words, string $language, array $names, array $config): bool
    {
        $comparison = $config['comparison'][$language] ?? [];
        $wishes = $config['not_similar_after'] ?? [];
        foreach ($words as $i => $word) {
            if (! in_array($word, $comparison, true) || in_array($words[$i - 1] ?? '', $wishes, true)) {
                continue;
            }
            $rest = ' '.implode(' ', array_slice($words, $i + 1)).' ';
            foreach ($names as $name) {
                // "like Treforma", "like the Treforma shop".
                if ($name !== '' && (str_starts_with($rest, ' '.$name.' ') || str_starts_with($rest, ' the '.$name.' '))) {
                    return true;
                }
            }
        }

        return false;
    }
}
