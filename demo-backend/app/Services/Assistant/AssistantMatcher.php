<?php

namespace App\Services\Assistant;

/**
 * Scores a visitor question against every question in the compiled index.
 *
 * For each admin question the score combines:
 *  - phrasing similarity: weighted word overlap with the question and its
 *    alternative phrasings (an identical phrasing scores 1),
 *  - keywords: admin keywords fully present in the question,
 *  - the CMS service or project the question is linked to, when named,
 * and is scaled by coverage, the share of the visitor's words the admin
 * question accounts for, so one shared generic word is not enough. Words
 * used by many questions weigh less. Synonym groups make equivalent terms
 * meet, and small typos match at reduced weight.
 */
class AssistantMatcher
{
    /** @var array<string, bool> */
    private array $fuzzyMemo = [];

    public function __construct(private AssistantNormalizer $normalizer) {}

    /** @return array{text: string, words: list<string>, tokens: list<string>, quantity: int|string|null, concepts: array<int, list<string>>, set: array<string, true>} */
    public function analyze(string $message, array $index): array
    {
        [$tokens, $origins] = $this->normalizer->tokenize($message, $index['locale'], $index['stopwords']);
        // "hello chat", "hi there team": words that only address the assistant leave a greeting.
        $addressees = array_fill_keys((array) config('assistant.addressees', []), true);
        if ($tokens !== [] && array_diff($tokens, array_keys($addressees)) === []) {
            $tokens = $origins = [];
        }
        [$tokens, $corrected] = $this->correct($tokens, $index);
        $concepts = $this->conceptPositions($tokens, $index['synonyms']);

        return [
            'text' => $this->normalizer->fold($message),
            'words' => $this->normalizer->words($message),
            'tokens' => $tokens,
            // The word each token came from, and tokens recovered from a typo ("webiste" → "website").
            'origins' => $origins,
            'corrected' => $corrected,
            'quantity' => $this->normalizer->quantity($message, $index['locale']),
            'concepts' => $concepts,
            'set' => array_fill_keys([...$tokens, ...array_merge(...array_values($concepts ?: [[]]))], true),
        ];
    }

    /**
     * The same question keeping only some of its tokens (the affirmative clauses of "not marketing,
     * an ERP system"), with positions renumbered and synonyms found again.
     *
     * @param  list<int>  $keep  token positions to keep
     */
    public function restrict(array $query, array $index, array $keep): array
    {
        $tokens = $origins = $corrected = [];
        foreach ($keep as $position) {
            if (isset($query['corrected'][$position])) {
                $corrected[count($tokens)] = $query['corrected'][$position];
            }
            $tokens[] = $query['tokens'][$position];
            $origins[] = $query['origins'][$position];
        }
        $concepts = $this->conceptPositions($tokens, $index['synonyms']);

        return ['tokens' => $tokens, 'origins' => $origins, 'corrected' => $corrected, 'concepts' => $concepts,
            'set' => array_fill_keys([...$tokens, ...array_merge(...array_values($concepts ?: [[]]))], true)] + $query;
    }

    /**
     * Typo recovery against the domain vocabulary (service names, admin phrases, synonyms and question
     * words): a word that is not in it becomes the one vocabulary word it is closest to, if that word
     * starts with the same letter, is at most two letters longer or shorter, is one edit away (two for
     * long words) and no other word is as close. Short words, digits and known words are never changed.
     *
     * @return array{0: list<string>, 1: array<int, string>} tokens, and the original of each corrected position
     */
    private function correct(array $tokens, array $index): array
    {
        $vocabulary = $index['vocabulary'] ?? [];
        $config = config('assistant.typos');
        $corrected = [];
        foreach ($tokens as $position => $token) {
            $length = strlen($token);
            if ($length < (int) $config['min_length'] || isset($vocabulary[$token]) || ctype_digit($token) || isset($index['df'][$token])) {
                continue;
            }
            $limit = $length >= (int) $config['long_length'] ? 2 : 1;
            $best = null;
            $ties = 0;
            foreach ($vocabulary as $word => $_) {
                $word = (string) $word;
                // Typing usually drops letters ("developmnt"); a shorter word only for long ones ("webbsite").
                if ($word[0] !== $token[0] || abs(strlen($word) - $length) > $limit || strlen($word) < 4 || (strlen($word) < $length && $length < 7)) {
                    continue;
                }
                $distance = $this->distance($token, $word, $limit);
                if ($distance > $limit) {
                    continue;
                }
                if (! $best || $distance < $best[1]) {
                    [$best, $ties] = [[$word, $distance], 0];
                } elseif ($distance === $best[1]) {
                    $ties++;
                }
            }
            if ($best && $ties === 0) {
                $corrected[$position] = $token;
                $tokens[$position] = $best[0];
            }
        }

        return [$tokens, $corrected];
    }

    /** Edits (insert, delete, replace, swap two neighbours) between two words, stopping early above the limit. */
    private function distance(string $a, string $b, int $limit): int
    {
        $m = strlen($a);
        $n = strlen($b);
        $d = [];
        for ($i = 0; $i <= $m; $i++) {
            $d[$i] = [$i];
        }
        for ($j = 0; $j <= $n; $j++) {
            $d[0][$j] = $j;
        }
        for ($i = 1; $i <= $m; $i++) {
            $rowMin = PHP_INT_MAX;
            for ($j = 1; $j <= $n; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $cost);
                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
                $rowMin = min($rowMin, $d[$i][$j]);
            }
            if ($rowMin > $limit) {
                return $limit + 1;
            }
        }

        return $d[$m][$n];
    }

    /**
     * Admin questions ranked by score, best first.
     *
     * @return list<array{id: int, score: float, exact: bool, signals: array, keywords: list<string>}>
     */
    public function rank(array $query, array $index, ?string $entityKey = null, array $context = []): array
    {
        if ($query['tokens'] === []) {
            return $this->smallTalk($query, $index);
        }
        $contextIntent = $index['intents'][$context['intent_id'] ?? 0] ?? null;
        $results = [];
        foreach ($index['intents'] as $id => $intent) {
            $result = $this->score($query, $intent, $index, $entityKey);
            if ($result === null) {
                continue;
            }
            if (! $result['exact'] && $contextIntent) {
                // Stay on topic: the previous answer's follow-ups and category get a small lift.
                if (in_array($id, $contextIntent['followups'], true)) {
                    $result['score'] += 0.08;
                    $result['signals']['context'] = 0.08;
                } elseif ($intent['category_id'] && $intent['category_id'] === $contextIntent['category_id']) {
                    $result['score'] += 0.05;
                    $result['signals']['context'] = 0.05;
                }
                $result['score'] = min(1.0, $result['score']);
            }
            $results[] = ['id' => $id] + $result;
        }

        usort($results, fn ($a, $b) => [$b['exact'], $b['score'], $index['intents'][$b['id']]['priority']] <=> [$a['exact'], $a['score'], $index['intents'][$a['id']]['priority']]);

        return $results;
    }

    /**
     * Questions made only of everyday words ("hi", "hello there", "what do you do?", "faleminderit")
     * match a phrasing of the same words; extra filler words are allowed, other words are not.
     */
    private function smallTalk(array $query, array $index): array
    {
        if ($query['words'] === []) {
            return [];
        }
        $stopwords = $this->normalizer->stopwords($index['locale']);
        $results = [];
        foreach ($index['intents'] as $id => $intent) {
            foreach ($intent['phrasings'] as $phrasing) {
                $words = array_fill_keys($phrasing['words'] ?? [], true);
                // Every word of the phrasing is present, and anything else is filler ("hello there").
                $missing = array_diff(array_keys($words), $query['words']);
                $extra = array_filter($query['words'], fn ($word) => ! isset($words[$word]) && ! isset($stopwords[$word]));
                $exact = $phrasing['text'] === $query['text'];
                if ($exact || ($phrasing['tokens'] === [] && $words && ! $missing && ! $extra)) {
                    $results[] = ['id' => $id, 'score' => 1.0, 'exact' => true,
                        'signals' => ['phrase' => 1.0, 'keywords' => 0.0, 'entity' => 0.0, 'coverage' => 1.0, 'small_talk' => ! $exact], 'keywords' => []];
                    break;
                }
            }
        }
        // The most specific phrasing first: "what do you do" over "do".
        usort($results, fn ($a, $b) => $index['intents'][$b['id']]['priority'] <=> $index['intents'][$a['id']]['priority']);

        return $results;
    }

    /**
     * Projects related to what the visitor named ("ecommerce", "Laravel", "web development"), best first.
     * Words that only say "project" or "show me" are ignored; synonyms count ("webshop" finds e-commerce work).
     *
     * @return array{terms: list<string>, keys: list<string>, label: string}
     */
    public function projectTopic(array $query, array $index, ?array $extraTokens = null, array $exclude = []): array
    {
        // Not topics: "project"/"show" words and the question's own wording ("a mund t'i shoh projektet").
        $generic = ($index['entity_words']['project'] ?? []) + ($index['entity_words']['service'] ?? []) + $exclude;
        $terms = array_values(array_filter($extraTokens ?? $query['tokens'], fn ($token) => ! isset($generic[$token]) && ! ctype_digit($token)));
        // Every spelling of a synonym the visitor used: "shitje online" also looks for "ecommerce". A
        // multi-word spelling counts only as a whole ("online shop"; "online booking" is not a shop).
        $variants = array_map(fn ($token) => [$token], $terms);
        foreach ($index['synonyms'] as $group) {
            if (isset($query['set'][$group['concept']])) {
                array_push($variants, ...$group['terms']);
            }
        }
        $variants = collect($variants)->filter(fn ($seq) => array_filter($seq, fn ($token) => ! isset($generic[$token])) !== [])
            ->unique(fn ($seq) => implode(' ', $seq))->values()->all();
        if ($terms === [] || $variants === []) {
            return ['terms' => [], 'keys' => [], 'label' => ''];
        }
        $keys = $hits = [];
        foreach ($index['entities'] as $key => $entity) {
            if ($entity['type'] !== 'project') {
                continue;
            }
            $related = array_fill_keys($entity['related'], true);
            $text = ' '.implode(' ', $entity['related']).' ';
            foreach ($variants as $seq) {
                $found = count($seq) === 1 ? isset($related[$seq[0]]) || $this->bestMatch($seq[0], $related) > 0 : str_contains($text, ' '.implode(' ', $seq).' ');
                if ($found) {
                    $keys[] = $key;
                    $hits += array_fill_keys($seq, true);
                }
            }
        }
        $keys = array_values(array_unique($keys));
        // Unknown words ("pls", "display") are not a topic; only recognisable subjects are: service words and synonyms.
        $known = $index['topics']['services'] ?? [];
        foreach ($index['synonyms'] as $group) {
            foreach ($group['terms'] as $term) {
                $known += array_fill_keys($term, true);
            }
        }
        $terms = array_values(array_filter($terms, fn ($term) => isset($hits[$term]) || isset($known[$term])));
        if ($terms === []) {
            return ['terms' => [], 'keys' => [], 'label' => ''];
        }
        // Only words that matched published content are ever repeated back; synonyms count for their group.
        $matched = array_values(array_filter($terms, fn ($term) => isset($hits[$term]) || array_filter($index['synonyms'], fn ($group) => isset($query['set'][$group['concept']]) && in_array([$term], $group['terms'], true) && array_intersect(array_merge(...$group['terms']), array_keys($hits)))));

        // The visitor's own words for the topic, for the answer's lead-in.
        $label = implode(' ', array_filter($query['words'], fn ($word) => in_array($this->normalizer->stem($word, $index['locale']), $matched, true)));

        return ['terms' => $terms, 'keys' => $keys, 'label' => $label];
    }

    /**
     * The service, subservice or project the question is about, when the rest of the question is the
     * usual wording around a name ("do you offer…", "the … project").
     *
     * @return array{key: string, score: float}|null
     */
    public function entity(array $query, array $index, ?array $subjects = null): ?array
    {
        foreach ($subjects ?? $this->subjects($query, $index) as $subject) {
            if ($subject['strong'] && $subject['coverage'] >= 0.6) {
                return ['key' => $subject['key'], 'score' => $subject['score']];
            }
        }

        return null;
    }

    /**
     * Every service, subservice and project the question names, best first. A service is named by
     * half of the distinctive words of one of its names (in any language, or an admin phrase linked
     * to it), by a word only one or two services use ("SEO", "cloud"), by a synonym of its whole
     * name ("website" for Web Development), or, as weaker evidence, by rare words of its CMS
     * description when they account for the whole question ("payment gateways"). Services that
     * share a single common word with the question ("integration") are listed as weak: never an
     * answer on their own, only choices when nothing else fits better.
     *
     * @return list<array{key: string, type: string, family: ?string, score: float, positions: list<int>, description: int, coverage: float, via: string, strong: bool}>
     */
    public function subjects(array $query, array $index): array
    {
        if ($query['tokens'] === [] || $index['entities'] === []) {
            return [];
        }
        $found = [];
        foreach ($index['entities'] as $key => $entity) {
            $isProject = $entity['type'] === 'project';
            $generic = $index['entity_words'][$isProject ? 'project' : 'service'] ?? [];
            $genericPositions = array_keys(array_filter($query['tokens'], fn ($token) => isset($generic[$token])));

            $best = null;
            $names = $isProject ? [$entity['tokens']] : $entity['names'];
            foreach ([...$names, ...($entity['aliases'] ?? [])] as $i => $name) {
                $alias = $i >= count($names);
                $match = $this->nameMatch($name, $query, $index, $generic, ! $isProject && ! $alias, whole: $alias, family: $entity['family'] ?? null);
                // An admin phrase counts only in full: "user interface" is not named by "user".
                if ($alias && $match && count($match['positions']) < $match['required']) {
                    continue;
                }
                if ($alias && $match) {
                    $match = ['score' => 1.0, 'via' => 'phrase', 'strong' => true] + $match;
                }
                if ($match && (! $best || [$match['strong'], count($match['positions']), $match['score']] > [$best['strong'], count($best['positions']), $best['score']])) {
                    $best = $match;
                }
            }

            // Supporting words from the description; on their own only when rare and covering the whole question.
            $desc = [];
            foreach ($query['tokens'] as $position => $token) {
                if (isset($entity['desc'][$token]) && ! isset($generic[$token]) && ! in_array($position, $best['positions'] ?? [], true)) {
                    $desc[] = $position;
                }
            }
            if (! $best) {
                $content = array_diff(array_keys($query['tokens']), $genericPositions);
                $rare = array_filter($desc, fn ($p) => ($index['desc_df'][$query['tokens'][$p]] ?? 9) <= (count($content) > 1 ? 2 : 1) && mb_strlen($query['tokens'][$p]) >= 4);
                if ($isProject || $content === [] || $rare === [] || array_diff($content, $desc) !== []) {
                    continue;
                }
                $best = ['positions' => [], 'score' => 0.0, 'via' => 'description', 'strong' => true];
            }

            // A word several areas use ("rrjete": networks or social networks) names this service when its own
            // description explains the rest of the question ("… për zyra": ICT & Network Solutions).
            if (! $best['strong'] && ! $isProject && $desc !== [] && ! ($best['fuzzy'] ?? false)) {
                $best['strong'] = true;
            }
            $covered = array_unique([...$best['positions'], ...$desc, ...$genericPositions]);
            $found[] = [
                'key' => $key, 'type' => $entity['type'], 'family' => $entity['family'] ?? null,
                'score' => round($best['score'], 3), 'positions' => $best['positions'], 'description' => count($desc),
                // Distinctive words explained: "website" names Web Development, but says less than "ecommerce".
                'explained' => count(array_diff($best['positions'], $genericPositions)),
                'coverage' => round(count($covered) / count($query['tokens']), 3), 'via' => $best['via'], 'strong' => $best['strong'],
                // Matched only through a near spelling ("automatic" ~ "automation"): never offered as a choice.
                'fuzzy' => $best['fuzzy'] ?? false,
                'length' => count($entity['tokens']),
            ];
        }

        // Clear matches first; then more of the question explained, more of the name, description
        // support; the main service before its subservices, and shorter names before longer ones.
        usort($found, fn ($a, $b) => [$b['strong'], $b['explained'], count($b['positions']), $b['score'], $b['description'], $a['type'] === 'subservice', $a['length']]
            <=> [$a['strong'], $a['explained'], count($a['positions']), $a['score'], $a['description'], $b['type'] === 'subservice', $b['length']]);

        return array_map(fn ($subject) => array_diff_key($subject, ['length' => 0]), $found);
    }

    /**
     * How well one name (a token sequence) is present in the question.
     *
     * @return array{positions: list<int>, score: float, via: string}|null
     */
    private function nameMatch(array $name, array $query, array $index, array $generic, bool $anchors, bool $whole = false, ?string $family = null): ?array
    {
        // Generic words ("Solutions", "Project-vertical") do not identify a name; a name made only of
        // generic words must be given in full.
        $identifying = array_values(array_unique(array_filter($name, fn ($token) => ! isset($generic[$token]))));
        // An admin phrase is matched word for word, generic words included ("software for business").
        $required = $whole ? array_values(array_unique($name)) : ($identifying ?: array_values(array_unique($name)));

        // A synonym of the whole name: "website" for "Web Development", "webshop" for "E-commerce Solutions".
        foreach ($index['synonyms'] as $group) {
            if (isset($query['set'][$group['concept']]) && (in_array($name, $group['terms'], true) || in_array($required, $group['terms'], true))) {
                $positions = array_keys(array_filter($query['concepts'], fn ($concepts) => in_array($group['concept'], $concepts, true)));

                return ['positions' => $positions, 'score' => 1.0, 'via' => 'synonym', 'strong' => true, 'required' => 0];
            }
        }

        $matched = 0.0;
        $positions = [];
        $anchored = false;
        $count = 0;
        $fuzzy = false;
        foreach ($required as $token) {
            $tokenConcepts = $this->conceptsOf([$token], $index['synonyms']);
            foreach ($query['tokens'] as $position => $queryToken) {
                // A word recovered from a typo counts a little less than one typed correctly.
                $weight = $this->sameWord($queryToken, $token) || array_intersect($query['concepts'][$position] ?? [], array_keys($tokenConcepts)) ? (isset($query['corrected'][$position]) ? 0.9 : 1.0)
                    : ($this->similarWords($queryToken, $token) ? (float) config('assistant.matching.fuzzy_weight', 0.8) : 0.0);
                if ($weight > 0) {
                    $fuzzy = $fuzzy || $weight < 0.9;
                    $matched += $weight;
                    $positions[] = $position;
                    $count++;
                    // A word that (almost) only one service area uses names it on its own: "SEO", "cloud", "AI", "CRM";
                    // "product" or "integration" are used by several areas and do not.
                    $anchored = $anchored || ($identifying !== [] && $weight >= 0.9 && $this->anchors($token, $family, $index));
                    break;
                }
            }
        }
        $score = $matched / count($required);
        // Projects: half of a name. Services: the whole distinctive name, two of its words, or one word of its area.
        $strong = $anchors ? ($score >= 1.0 || ($count >= 2 && $score >= 0.5) || $anchored) : ($identifying !== [] ? $score >= 0.5 : $score >= 1.0);
        // Projects need a clear match; a service sharing one distinctive word is kept as a weak candidate.
        if ($positions === [] || (! $strong && (! $anchors || $identifying === []))) {
            return null;
        }

        return ['positions' => array_values(array_unique($positions)), 'score' => $score, 'via' => 'name', 'strong' => $strong, 'required' => count($required), 'fuzzy' => $fuzzy];
    }

    /**
     * A phrase an admin marked as a capability that is not simply a published service ("logo":
     * related; "branding": not offered), when one of its terms is fully in the question.
     *
     * @return array{id: int, status: string, term: string, entities: list<string>, positions: list<int>}|null
     */
    public function capability(array $query, array $index): ?array
    {
        $best = null;
        foreach ($index['capabilities'] ?? [] as $capability) {
            foreach ($capability['terms'] as $term) {
                $positions = [];
                foreach ($term['tokens'] as $token) {
                    $tokenConcepts = array_keys($this->conceptsOf([$token], $index['synonyms']));
                    foreach ($query['tokens'] as $position => $queryToken) {
                        if (! in_array($position, $positions, true) && ($this->sameWord($queryToken, $token) || $this->similarWords($queryToken, $token)
                            || array_intersect($query['concepts'][$position] ?? [], $tokenConcepts))) {
                            $positions[] = $position;

                            continue 2;
                        }
                    }

                    continue 2;
                }
                if (! $best || count($positions) > count($best['positions'])) {
                    $best = ['id' => $capability['id'], 'status' => $capability['status'], 'term' => $term['text'],
                        'entities' => $capability['entities'], 'positions' => $positions];
                }
            }
        }

        return $best;
    }

    /**
     * Share of the question an admin question accounts for when the services and projects it names
     * (and the usual words around them) count as accounted for: "how much would an ecommerce shop
     * cost?" is a pricing question about e-commerce.
     *
     * @param  list<int>  $covered  positions already accounted for
     */
    public function coverageWith(array $query, array $intent, array $covered): float
    {
        if ($query['tokens'] === []) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($query['tokens'] as $position => $token) {
            $total += in_array($position, $covered, true) ? 1.0 : $this->tokenMatch($token, $position, $query, $intent['vocab'], $intent['concepts']);
        }

        return $total / count($query['tokens']);
    }

    private function score(array $query, array $intent, array $index, ?string $entityKey): ?array
    {
        foreach ($intent['negative'] as $negative) {
            if (array_diff($negative, $query['tokens']) === []) {
                return null;
            }
        }

        $exact = false;
        $phrase = 0.0;
        foreach ($intent['phrasings'] as $phrasing) {
            if ($phrasing['text'] === $query['text'] || ($phrasing['tokens'] !== [] && $phrasing['tokens'] === $query['tokens'])) {
                $exact = true;
                $phrase = 1.0;
                break;
            }
            $phrase = max($phrase, $this->similarity($query, $phrasing['tokens'], $index));
        }

        $keywordScore = 0.0;
        $matchedKeywords = [];
        foreach ($intent['keywords'] as $keyword) {
            $weight = $this->keywordMatch($keyword['tokens'], $query, $index);
            if ($weight > 0) {
                $keywordScore += $weight;
                $matchedKeywords[] = $keyword['label'];
            }
        }
        $keywordScore = min(1.0, $keywordScore);

        $entity = $entityKey !== null && $intent['entity'] === $entityKey ? 1.0 : 0.0;

        $vocabConcepts = $intent['concepts'];
        // A list question may be narrowed by any project or service topic: "ecommerce projects".
        $topic = match ($intent['source_type']) {
            'projects' => $index['topics']['projects'] ?? [], 'services' => $index['topics']['services'] ?? [], default => []
        };
        $covered = 0.0;
        foreach ($query['tokens'] as $position => $token) {
            $match = $this->tokenMatch($token, $position, $query, $intent['vocab'], $vocabConcepts);
            if ($match < 1.0 && $topic) {
                $match = max($match, ($query['concepts'][$position] ?? []) ? 1.0 : $this->bestMatch($token, $topic));
            }
            $covered += $match;
        }
        $coverage = $covered / count($query['tokens']);

        $base = min(1.0, 0.55 * $phrase + 0.45 * $keywordScore + 0.2 * $entity);
        $score = $exact ? 1.0 : max(0.0, min(1.0, $base * (0.5 + 0.5 * $coverage) + 0.03 * $intent['priority']));

        if ($score <= 0 && ! $matchedKeywords) {
            return null;
        }

        return [
            'score' => round($score, 3),
            'exact' => $exact,
            'signals' => ['phrase' => round($phrase, 3), 'keywords' => round($keywordScore, 3), 'entity' => $entity, 'coverage' => round($coverage, 3)],
            'keywords' => $matchedKeywords,
        ];
    }

    /** Weighted F1 overlap between the question and one phrasing. */
    private function similarity(array $query, array $phrasing, array $index): float
    {
        $set = array_fill_keys($phrasing, true);
        $concepts = $this->conceptsOf($phrasing, $index['synonyms']);

        $qTotal = $qMatched = 0.0;
        foreach ($query['tokens'] as $position => $token) {
            $weight = $this->weight($token, $index);
            $qTotal += $weight;
            $qMatched += $weight * $this->tokenMatch($token, $position, $query, $set, $concepts);
        }

        $pConcepts = $this->conceptPositions($phrasing, $index['synonyms']);
        $pTotal = $pMatched = 0.0;
        foreach (array_unique($phrasing) as $position => $token) {
            $weight = $this->weight($token, $index);
            $pTotal += $weight;
            $shared = array_intersect($pConcepts[$position] ?? [], array_keys($query['set']));
            $pMatched += $weight * ($shared ? 1.0 : $this->bestMatch($token, $query['set']));
        }

        $precision = $qTotal > 0 ? $qMatched / $qTotal : 0;
        $recall = $pTotal > 0 ? $pMatched / $pTotal : 0;

        return $precision + $recall > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;
    }

    /** A keyword counts when all its words (or an equivalent synonym) appear; specific and multi-word keywords count more. */
    private function keywordMatch(array $keyword, array $query, array $index): float
    {
        foreach ($this->conceptsOf($keyword, $index['synonyms']) as $concept => $_) {
            if (isset($query['set'][$concept]) && $this->conceptCoversKeyword($concept, $keyword, $index['synonyms'])) {
                return min(1.0, $this->weight($concept, $index) + (count($keyword) > 1 ? 0.25 : 0));
            }
        }

        $weights = $factor = 0.0;
        foreach ($keyword as $token) {
            $match = $this->bestMatch($token, $query['set']);
            if ($match <= 0) {
                return 0.0;
            }
            $factor += $match;
            $weights += $this->weight($token, $index);
        }
        $count = count($keyword);

        return min(1.0, ($weights / $count) * ($factor / $count) + ($count > 1 ? 0.25 : 0));
    }

    /** Whether a synonym group stands for the whole keyword, not just one of its words. */
    private function conceptCoversKeyword(string $concept, array $keyword, array $synonyms): bool
    {
        foreach ($synonyms as $group) {
            if ($group['concept'] === $concept) {
                return in_array($keyword, $group['terms'], true);
            }
        }

        return false;
    }

    /** 1 for the same word or synonym, the fuzzy weight for a near miss, otherwise 0. */
    private function tokenMatch(string $token, int $position, array $query, array $set, array $concepts): float
    {
        if (isset($set[$token])) {
            return 1.0;
        }
        foreach ($query['concepts'][$position] ?? [] as $concept) {
            if (isset($concepts[$concept])) {
                return 1.0;
            }
        }

        return $this->bestMatch($token, $set);
    }

    private function bestMatch(string $token, array $set): float
    {
        if (isset($set[$token])) {
            return 1.0;
        }
        foreach (array_keys($set) as $candidate) {
            if ($this->similarWords($token, (string) $candidate)) {
                return (float) config('assistant.matching.fuzzy_weight', 0.8);
            }
        }

        return 0.0;
    }

    /** Typo and word-form tolerance: one edit (two for long words), or a shared stem like "kontakt"/"kontaktoni". */
    private function similarWords(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if ($a === '' || $b === '' || $a[0] === '~' || $b[0] === '~' || ctype_digit($a) || ctype_digit($b)) {
            return false;
        }
        $key = $a < $b ? "$a|$b" : "$b|$a";
        if (isset($this->fuzzyMemo[$key])) {
            return $this->fuzzyMemo[$key];
        }

        [$short, $long] = strlen($a) <= strlen($b) ? [$a, $b] : [$b, $a];
        $minimum = (int) config('assistant.matching.fuzzy_min_length', 5);
        $similar = false;
        if (strlen($short) >= 4 && strlen($long) >= $minimum) {
            if (strlen($short) >= $minimum && str_starts_with($long, $short) && strlen($long) - strlen($short) <= 4) {
                $similar = true;
            } elseif ($short[0] === $long[0] && strlen($long) - strlen($short) <= 2) {
                $similar = levenshtein($short, $long) <= (strlen($long) >= 9 ? 2 : 1) || $this->swapped($short, $long);
            }
        }

        return $this->fuzzyMemo[$key] = $similar;
    }

    /** Whether a name word belongs (almost) only to one service area: at least two thirds of the services using it are in that area. */
    private function anchors(string $token, ?string $family, array $index): bool
    {
        $families = $index['name_families'][$token] ?? [];
        $total = array_sum($families);

        return $family !== null && $total > 0 && count($families) <= 2 && ($families[$family] ?? 0) * 3 >= $total * 2;
    }

    /** The same word, also when one is a plural the stemmer keeps ("api" / "apis"). */
    private function sameWord(string $a, string $b): bool
    {
        return $a === $b || $a.'s' === $b || $b.'s' === $a;
    }

    /** "contcat" for "contact": two neighbouring letters typed the other way round. */
    private function swapped(string $a, string $b): bool
    {
        if (strlen($a) !== strlen($b)) {
            return false;
        }
        $diff = [];
        for ($i = 0; $i < strlen($a); $i++) {
            if ($a[$i] !== $b[$i]) {
                $diff[] = $i;
            }
        }

        return count($diff) === 2 && $diff[1] === $diff[0] + 1 && $a[$diff[0]] === $b[$diff[1]] && $a[$diff[1]] === $b[$diff[0]];
    }

    private function weight(string $token, array $index): float
    {
        return 1 / sqrt(max(1, $index['df'][$token] ?? 1));
    }

    /**
     * Synonym groups found in a token sequence, by position.
     *
     * @return array<int, list<string>>
     */
    private function conceptPositions(array $tokens, array $synonyms): array
    {
        $positions = [];
        foreach ($synonyms as $group) {
            foreach ($group['terms'] as $term) {
                $length = count($term);
                for ($i = 0; $i + $length <= count($tokens); $i++) {
                    if (array_slice($tokens, $i, $length) === $term) {
                        for ($j = $i; $j < $i + $length; $j++) {
                            $positions[$j][] = $group['concept'];
                        }
                    }
                }
            }
        }

        return array_map(fn ($list) => array_values(array_unique($list)), $positions);
    }

    /** @return array<string, true> synonym groups present in a token list */
    private function conceptsOf(array $tokens, array $synonyms): array
    {
        $found = [];
        foreach ($this->conceptPositions($tokens, $synonyms) as $concepts) {
            foreach ($concepts as $concept) {
                $found[$concept] = true;
            }
        }

        return $found;
    }
}
