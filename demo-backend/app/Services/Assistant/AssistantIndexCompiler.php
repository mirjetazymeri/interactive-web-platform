<?php

namespace App\Services\Assistant;

use App\Models\AssistantIntent;
use App\Models\AssistantKnowledgeProfile;
use App\Models\AssistantProfile;
use App\Models\AssistantSynonym;
use App\Models\Locale;
use App\Services\Assistant\Resolvers\ContactResolver;
use App\Services\Assistant\Resolvers\PagesResolver;
use App\Services\Assistant\Resolvers\ProjectsResolver;
use App\Services\Assistant\Resolvers\ServicesResolver;
use App\Services\Assistant\Resolvers\Text;
use App\Services\SettingService;

/**
 * Builds the matching index for one channel and language: admin questions
 * with their tokens, synonym groups, word weights and the CMS content
 * (services, projects, contact details, page links) answers may show. The
 * result is a plain array so it can be cached.
 */
class AssistantIndexCompiler
{
    public function __construct(
        private AssistantNormalizer $normalizer,
        private PagesResolver $pages,
        private ServicesResolver $services,
        private ProjectsResolver $projects,
        private ContactResolver $contact,
        private SettingService $settings,
    ) {}

    public function compile(int $channelId, string $locale): array
    {
        $company = trim((string) $this->settings->get('general.app_name', '', $channelId));
        $stopwords = array_fill_keys($this->normalizer->words($company), true);
        $tokens = fn (string $text) => $this->normalizer->tokens($text, $locale, $stopwords);
        $vocabulary = AssistantSynonym::query()->forChannel($channelId)->where('active', true)
            ->where(fn ($q) => $q->whereNull('locale')->orWhere('locale', $locale))->orderBy('id')->get();
        $synonyms = $this->synonyms($vocabulary, $tokens);
        $pages = $this->pages->reachable($channelId);

        $services = $this->services->all($channelId, $locale);
        $projects = $this->projects->all($channelId, $locale);
        $contact = $this->contact->details($channelId);
        $language = $this->normalizer->language($locale);
        $generic = [
            'service' => $this->wordSet((array) config('assistant.entity_words.'.$language.'.service', []), $tokens),
            'project' => $this->wordSet((array) config('assistant.entity_words.'.$language.'.project', []), $tokens),
        ];
        // Names in the other published languages too: an English name still finds "Zhvillim Web", and
        // synonyms written for "Web Development" apply to the Albanian name.
        $otherNames = [];
        foreach ($this->locales() as $other) {
            if ($other !== $locale) {
                foreach ($this->services->all($channelId, $other) as $service) {
                    $otherNames[$service['key']][] = $this->normalizer->tokens($service['name'], $other, $stopwords);
                }
            }
        }
        $phrases = $this->phrases($vocabulary, $tokens);
        $entities = $this->entities($services, $projects, $locale, $tokens, $otherNames, $phrases, $synonyms, $generic);

        // The published Company Knowledge Profile's introduction, for questions that use it.
        $knowledge = $this->knowledgeIntroduction($channelId, $locale);

        $intents = [];
        $records = AssistantIntent::query()->forChannel($channelId)->where('active', true)
            ->with(['translations', 'followups' => fn ($q) => $q->where('active', true)])
            ->orderBy('position')->orderBy('id')->get();
        foreach ($records as $intent) {
            $intents[$intent->id] = $this->intent($intent, $locale, $tokens, $pages, $entities, $contact, $services, $projects, $knowledge);
        }

        // Word weights: a word used by many questions says little about which one is meant.
        $df = [];
        foreach ($intents as &$intent) {
            $all = $this->withConcepts($intent['vocab'], $synonyms);
            $intent['concepts'] = array_diff_key($all, $intent['vocab']);
            foreach (array_keys($all) as $token) {
                $df[$token] = ($df[$token] ?? 0) + 1;
            }
        }
        unset($intent);
        $named = array_filter($entities, fn ($entity) => $entity['type'] !== 'project');

        return [
            'channel_id' => $channelId,
            'locale' => $locale,
            'language' => $language,
            'company' => $company !== '' ? $company : null,
            'profile' => $this->profile($channelId, $locale),
            'stopwords' => $stopwords,
            'synonyms' => $synonyms,
            'intents' => $intents,
            'suggested' => $records->where('is_suggested', true)->sortBy([['suggested_position', 'asc'], ['id', 'asc']])->pluck('id')->values()->all(),
            'df' => $df,
            'entities' => $entities,
            // How many services use a word in a name or description: "seo" names one service, "development" several.
            'name_df' => $this->frequency(array_map(fn ($entity) => array_merge(...$entity['names']), $named)),
            // For each name word, how many services of each area use it: "AI" belongs to AI & Automation, "product" to no single area.
            'name_families' => $this->families($named),
            // Words a typo may be recovered to: service names and phrases, synonyms, question wording, generic words.
            'vocabulary' => $this->vocabulary($intents, $entities, $synonyms, $phrases, $generic),
            'desc_df' => $this->frequency(array_map(fn ($entity) => array_keys($entity['desc']), $named)),
            'capabilities' => $this->capabilities($phrases, $entities),
            // Words a request may narrow a list by: "ecommerce projects", "projects with Laravel".
            'topics' => [
                'projects' => array_fill_keys(array_merge(...array_values(array_map(fn ($e) => $e['related'], array_filter($entities, fn ($e) => $e['type'] === 'project'))) ?: [[]]), true),
                'services' => array_fill_keys(array_merge(...array_values(array_map(fn ($e) => $e['related'], $named)) ?: [[]]), true),
            ],
            'entity_words' => $generic,
            // Words this language's content uses, for telling the visitor's language apart.
            'lexicon' => $this->lexicon($intents, $services),
            'act_intents' => $this->actIntents($intents),
            'contact_action' => $contact['page'] ? $this->pages->action($locale, $contact['page']['path'], null, __('assistant.public.contact_page', [], $locale)) : null,
            'booking_action' => $contact['page'] && $contact['booking'] ? $this->pages->action($locale, $contact['page']['path'], 'book-a-meeting', __('assistant.public.discuss_project', [], $locale)) : null,
        ];
    }

    /** Locales an index can be built for: the default language and the active CMS languages. */
    public function locales(): array
    {
        return array_values(array_unique([(string) config('app.locale'), ...Locale::activeCodes()]));
    }

    /** The introduction of a published Company Knowledge Profile in the locale (base text as fallback); null for drafts. */
    private function knowledgeIntroduction(int $channelId, string $locale): ?string
    {
        $profile = AssistantKnowledgeProfile::query()->forChannel($channelId)->where('status', 'published')->with('translations')->first();

        return $profile ? (Text::plain($profile->translated('introduction', $locale), 600) ?: null) : null;
    }

    private function profile(int $channelId, string $locale): ?array
    {
        $profile = AssistantProfile::query()->forChannel($channelId)->with('translations')->first();
        if (! $profile) {
            return null;
        }
        $text = [];
        foreach (AssistantProfile::TEXT_FIELDS as $field) {
            $text[$field] = Text::plain($profile->translated($field, $locale), 1000) ?: null;
        }

        return $text + [
            'enabled' => $profile->enabled,
            'show_on_mobile' => $profile->show_on_mobile,
            'log_unanswered' => $profile->log_unanswered,
            // Admin wordings for this language, as plain text.
            'templates' => array_map(fn ($list) => array_values(array_filter(array_map(fn ($line) => Text::plain((string) $line, 300), (array) $list))),
                array_intersect_key((array) ($profile->response_templates[$locale] ?? []), AssistantProfile::TEMPLATE_SETS)),
        ];
    }

    /** @return list<array{concept: string, terms: list<list<string>>}> */
    private function synonyms($records, callable $tokens): array
    {
        $groups = [];
        foreach ($records as $synonym) {
            $terms = collect($synonym->terms ?? [])->map(fn ($term) => $tokens((string) $term))
                ->filter()->unique(fn ($seq) => implode(' ', $seq))->values()->all();
            if (count($terms) >= 2) {
                $groups[] = ['concept' => '~'.$synonym->id, 'terms' => $terms];
            }
        }

        return $groups;
    }

    /**
     * Synonym groups that name services or carry a capability status ("chatbot" → AI Assistants & Chatbots;
     * "logo": related, the team confirms the scope).
     *
     * @return list<array{id: int, capability: string, refs: list<string>, terms: list<array{text: string, tokens: list<string>}>}>
     */
    private function phrases($records, callable $tokens): array
    {
        $out = [];
        foreach ($records as $synonym) {
            if (! $synonym->isServicePhrase()) {
                continue;
            }
            $terms = [];
            foreach ($synonym->terms ?? [] as $term) {
                $seq = $tokens((string) $term);
                if ($seq !== []) {
                    $terms[] = ['text' => Text::plain((string) $term, 100), 'tokens' => $seq];
                }
            }
            if ($terms !== []) {
                $out[] = ['id' => $synonym->id, 'capability' => $synonym->capability ?? 'confirmed', 'refs' => array_values(array_map('strval', (array) $synonym->service_refs)), 'terms' => $terms];
            }
        }

        return $out;
    }

    /**
     * Services, subservices and projects visitors may name, with the words that identify them: names in
     * every language and admin phrases for services; for projects, the services their published type,
     * category or description name ("E-commerce" relates a shop to E-commerce Solutions).
     */
    private function entities(array $services, array $projects, string $locale, callable $tokens, array $otherNames, array $phrases, array $synonyms, array $generic): array
    {
        $entities = [];
        foreach ($services as $service) {
            $own = $tokens($service['name']);
            $names = [$own, ...($otherNames[$service['key']] ?? [])];
            $aliases = [];
            foreach ($phrases as $phrase) {
                if ($phrase['capability'] === 'confirmed' && $phrase['refs'] === [$service['ref']]) {
                    array_push($aliases, ...array_column($phrase['terms'], 'tokens'));
                }
            }
            $unique = fn (array $list) => array_values(collect($list)->filter()->unique(fn ($seq) => implode(' ', $seq))->all());
            $entities[$service['key']] = [
                'type' => $service['type'],
                'name' => $service['name'],
                'tokens' => $own,
                'ref' => $service['ref'],
                'parent' => $service['parent_key'],
                'parent_name' => $service['parent'],
                // The top-level service: subservices of one service are one family.
                'family' => $service['parent_key'] ?? $service['key'],
                // CMS names in every language (half of the distinctive words is enough), and admin phrases (whole phrase only).
                'names' => $unique($names),
                'aliases' => $unique($aliases),
                // Words from the CMS description: supporting evidence, never enough on their own for a name.
                'desc' => array_fill_keys($tokens($service['text']), true),
                'card' => ['type' => 'service', 'title' => $service['name'], 'subtitle' => $service['parent'], 'text' => $service['text'],
                    'href' => PagesResolver::href($locale, $service['path'], $service['anchor'])],
                'action' => $this->pages->action($locale, $service['path'], $service['anchor'], __('assistant.public.view_service', [], $locale)),
                'related' => $tokens(trim($service['name'].' '.($service['parent'] ?? ''))),
            ];
        }
        $entities = array_filter($entities, fn ($entity) => $entity['tokens'] !== []);
        $named = $entities;

        foreach ($projects as $project) {
            $related = $this->projectServices($project, $named, $tokens, $synonyms, $generic['service']);
            $relatedNames = array_map(fn ($key) => $named[$key]['name'], $related);
            $entities[$project['key']] = [
                'type' => 'project',
                'name' => $project['name'],
                'tokens' => $tokens($project['name']),
                'card' => $this->projectCard($project, $locale),
                'action' => $this->pages->action($locale, $project['path'], null, __('assistant.public.view_project', [], $locale)),
                // Words used to relate a project to a service the visitor asked about.
                'related' => $tokens(implode(' ', [$project['name'], $project['category'], $project['type'], ...$project['technologies'], $project['text'], ...$relatedNames])),
                'services' => $related,
                'list_path' => $project['list_path'],
                // Fuller entries come first in lists: featured, with an image, with a real description.
                'quality' => ($project['featured'] ? 4 : 0) + ($project['image'] ? 2 : 0) + (mb_strlen($project['text']) >= 40 ? 2 : 0),
            ];
        }

        return array_filter($entities, fn ($entity) => $entity['tokens'] !== []);
    }

    /**
     * Services a project's own published words name. Its type and category ("E-commerce") name a
     * service by its name, an admin phrase or a synonym; its longer description only by a full
     * multi-word service name, since descriptions also mention plans and context ("customers will
     * create their own 3D models" is not a 3D product experience).
     *
     * @return list<string> entity keys
     */
    private function projectServices(array $project, array $services, callable $tokens, array $synonyms, array $generic): array
    {
        $strong = $tokens($project['type'].' '.$project['category']);
        $strongSet = array_fill_keys($strong, true);
        $concepts = $this->conceptsIn($strong, $synonyms);
        $longText = ' '.implode(' ', $tokens($project['details'])).' ';

        $related = [];
        foreach ($services as $key => $service) {
            foreach ([...$service['names'], ...$service['aliases']] as $i => $name) {
                $identifying = array_values(array_filter($name, fn ($token) => ! isset($generic[$token]))) ?: $name;
                $named = array_diff($identifying, array_keys($strongSet)) === []
                    || ($i < count($service['names']) && count($name) >= 2 && str_contains($longText, ' '.implode(' ', $name).' '))
                    || array_filter($synonyms, fn ($group) => isset($concepts[$group['concept']]) && (in_array($identifying, $group['terms'], true) || in_array($name, $group['terms'], true)));
                if ($named) {
                    $related[] = $key;
                    break;
                }
            }
        }

        return $related;
    }

    /** @return array<string, true> synonym groups with a term in the token sequence */
    private function conceptsIn(array $tokens, array $synonyms): array
    {
        $text = ' '.implode(' ', $tokens).' ';
        $found = [];
        foreach ($synonyms as $group) {
            foreach ($group['terms'] as $term) {
                if (str_contains($text, ' '.implode(' ', $term).' ')) {
                    $found[$group['concept']] = true;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Phrases that are not simply another name for one published service: related or not offered, or confirmed
     * for several services or none. A phrase whose linked services are all unpublished is left out entirely.
     */
    private function capabilities(array $phrases, array $entities): array
    {
        $byRef = [];
        foreach ($entities as $key => $entity) {
            if (isset($entity['ref'])) {
                $byRef[$entity['ref']] = $key;
            }
        }
        $out = [];
        foreach ($phrases as $phrase) {
            $linked = array_values(array_filter(array_map(fn ($ref) => $byRef[$ref] ?? null, $phrase['refs'])));
            // A confirmed phrase for one service is simply another name for it (see entities()).
            if ($phrase['capability'] === 'confirmed' && count($phrase['refs']) === 1) {
                continue;
            }
            if ($phrase['refs'] !== [] && $linked === []) {
                continue;
            }
            $out[] = ['id' => $phrase['id'], 'status' => $phrase['capability'], 'terms' => $phrase['terms'], 'entities' => $linked];
        }

        return $out;
    }

    /** @return array<string, array<string, int>> name word → service area → number of services */
    private function families(array $services): array
    {
        $out = [];
        foreach ($services as $entity) {
            foreach (array_unique(array_merge(...$entity['names'])) as $token) {
                $out[$token][$entity['family']] = ($out[$token][$entity['family']] ?? 0) + 1;
            }
        }

        return $out;
    }

    /** @return array<string, true> */
    private function vocabulary(array $intents, array $entities, array $synonyms, array $phrases, array $generic): array
    {
        $words = $generic['service'] + $generic['project'];
        foreach ($intents as $intent) {
            $words += $intent['vocab'];
        }
        foreach ($entities as $entity) {
            foreach ([$entity['tokens'], ...($entity['names'] ?? []), ...($entity['aliases'] ?? [])] as $name) {
                $words += array_fill_keys($name, true);
            }
        }
        foreach ([...array_merge(...array_column($synonyms, 'terms') ?: [[]]), ...array_column(array_merge(...array_column($phrases, 'terms') ?: [[]]), 'tokens')] as $term) {
            $words += array_fill_keys($term, true);
        }

        return array_filter($words, fn ($_, $word) => strlen((string) $word) >= 4 && ! ctype_digit((string) $word), ARRAY_FILTER_USE_BOTH);
    }

    /** @return array<string, int> in how many lists each token appears */
    private function frequency(array $lists): array
    {
        $df = [];
        foreach ($lists as $list) {
            foreach (array_unique($list) as $token) {
                $df[$token] = ($df[$token] ?? 0) + 1;
            }
        }

        return $df;
    }

    /** @return array<string, true> folded words of the questions and service texts in this language */
    private function lexicon(array $intents, array $services): array
    {
        $words = [];
        foreach ($intents as $intent) {
            $words += array_fill_keys(explode(' ', trim($intent['wording'])), true);
        }
        foreach ($services as $service) {
            $words += array_fill_keys($this->normalizer->words($service['name'].' '.$service['text']), true);
        }
        unset($words['']);

        return $words;
    }

    /**
     * Questions that answer a request type, by their own wording: pricing questions mention "cost" or
     * "price", timeline questions "how long". Cue words of every language apply, since a question may be
     * untranslated.
     *
     * @return array<string, list<int>>
     */
    private function actIntents(array $intents): array
    {
        $out = [];
        foreach (['pricing', 'timeline', 'contact'] as $act) {
            $cues = collect(config('assistant.acts', []))->filter(fn ($lists) => is_array($lists) && isset($lists[$act]))->pluck($act)->flatten()->all();
            $out[$act] = array_keys(array_filter($intents, fn ($intent) => array_filter($cues, fn ($cue) => str_contains($intent['wording'], ' '.$cue.' ')) !== []));
        }
        // "Will my website work on phones?": questions worded about a site on mobile devices.
        $responsive = array_merge(...array_values(array_filter((array) config('assistant.responsive', []), 'is_array')) ?: [[]]);
        $out['responsive'] = array_keys(array_filter($intents, fn ($intent) => array_filter($responsive, fn ($cue) => str_contains($intent['wording'], ' '.$cue.' ')) !== []));
        foreach (['projects', 'services'] as $source) {
            $out[$source] = array_keys(array_filter($intents, fn ($intent) => $intent['source_type'] === $source));
        }

        return $out;
    }

    private function intent(AssistantIntent $intent, string $locale, callable $tokens, array $pages, array $entities, array $contact, array $services, array $projects, ?string $knowledge = null): array
    {
        $question = (string) $intent->translated('question', $locale);
        $phrasings = [];
        foreach ([$question, ...$intent->translatedList('phrasings', $locale)] as $text) {
            $seq = $tokens($text);
            $folded = $this->normalizer->fold($text);
            // Phrasings made only of everyday words ("hi", "what do you do?") are kept for whole-phrase matching.
            if ($folded !== '') {
                $phrasings[] = ['text' => $folded, 'tokens' => $seq, 'words' => array_values(array_unique($this->normalizer->words($text)))];
            }
        }
        $keywords = [];
        foreach ($intent->translatedList('keywords', $locale) as $keyword) {
            $seq = $tokens($keyword);
            if ($seq !== []) {
                $keywords[] = ['label' => $keyword, 'tokens' => $seq];
            }
        }
        $negative = array_values(array_filter(array_map($tokens, $intent->translatedList('negative_keywords', $locale))));

        [$actions, $cards, $entityKey] = $this->source($intent, $locale, $entities, $contact, $services, $projects);
        $entityTerms = $entityKey ? [$entities[$entityKey]['tokens']] : [];

        if ($page = $pages[$intent->page_id] ?? null) {
            $label = $intent->translated('link_label', $locale) ?: $this->pages->label($page['page'], $locale);
            array_unshift($actions, $this->pages->action($locale, $page['path'], $intent->page_anchor, Text::plain($label, 80)));
        }

        $vocab = [];
        foreach ([...array_column($phrasings, 'tokens'), ...array_column($keywords, 'tokens'), ...$entityTerms] as $seq) {
            foreach ($seq as $token) {
                $vocab[$token] = true;
            }
        }

        return [
            'id' => $intent->id,
            'category_id' => $intent->assistant_category_id,
            'priority' => max(-1, min(1, (int) $intent->priority)),
            'question' => Text::plain($question, 255),
            // "Company profile": the verified introduction when published, otherwise the admin's own answer.
            'answer' => $intent->source_type === 'company' && $knowledge ? $knowledge : Text::plain($intent->translated('answer', $locale), 3000),
            'phrasings' => $phrasings,
            'keywords' => $keywords,
            'negative' => $negative,
            'entity' => $entityKey,
            'entity_terms' => $entityTerms,
            'vocab' => $vocab,
            'source_type' => $intent->source_type,
            'actions' => $this->unique(array_filter($actions)),
            'cards' => $cards,
            'followups' => $intent->followups->pluck('id')->all(),
            // The question's own folded wording, for recognising what kind of request it answers.
            'wording' => ' '.implode(' ', [...array_column($phrasings, 'text'), ...array_map(fn ($k) => $this->normalizer->fold($k['label']), $keywords)]).' ',
        ];
    }

    /** Actions and cards from the CMS content the question refers to; never invented text. */
    private function source(AssistantIntent $intent, string $locale, array $entities, array $contact, array $services, array $projects): array
    {
        $type = $intent->source_type;
        $id = (string) $intent->source_id;

        if ($type === 'contact') {
            $actions = [];
            if ($contact['page']) {
                $actions[] = $this->pages->action($locale, $contact['page']['path'], null, __('assistant.public.contact_page', [], $locale));
                if ($contact['booking']) {
                    $actions[] = $this->pages->action($locale, $contact['page']['path'], 'book-a-meeting', __('assistant.public.book_meeting', [], $locale));
                }
            }
            if ($contact['email']) {
                $actions[] = ['type' => 'email', 'label' => $contact['email'], 'href' => 'mailto:'.$contact['email']];
            }
            if ($contact['phone']) {
                $actions[] = ['type' => 'phone', 'label' => $contact['phone'], 'href' => 'tel:'.preg_replace('/[^+\d]/', '', $contact['phone'])];
            }
            $card = array_intersect_key($contact, array_flip(['email', 'phone', 'address', 'hours', 'maps_url']));

            return [$actions, array_filter($card) ? [['type' => 'contact'] + $card] : [], null];
        }

        if ($type === 'services' || $type === 'company') {
            $top = array_values(array_filter($services, fn ($service) => $service['type'] === 'service'));
            $cards = array_map(fn ($service) => $entities[$service['key']]['card'] ?? null, array_slice($top, 0, 8));
            $action = $top ? $this->pages->action($locale, $top[0]['path'], $top[0]['anchor'], __('assistant.public.view_services', [], $locale)) : null;

            return [[$action], array_values(array_filter($cards)), null];
        }

        if ($type === 'projects') {
            // The page that lists projects; the home page only when no other page shows them.
            $paths = array_column($projects, 'list_path');
            $path = collect($paths)->first(fn ($path) => $path !== 'home') ?? ($paths[0] ?? null);
            $action = $path ? $this->pages->action($locale, $path, null, __('assistant.public.view_projects', [], $locale)) : null;

            return [[$action], [], null];
        }

        $key = in_array($type, ['service', 'subservice', 'project'], true) ? $type.':'.$id : null;
        if ($key && ! isset($entities[$key]) && $type !== 'project') {
            // A service chosen from another widget showing the same services: match by its CMS id.
            $suffix = substr($id, (int) strpos($id, ':'));
            $key = collect(array_keys($entities))->first(fn ($candidate) => str_starts_with($candidate, $type.':') && str_ends_with($candidate, $suffix)) ?? $key;
        }
        if ($key && isset($entities[$key])) {
            return [[$entities[$key]['action']], [$entities[$key]['card']], $key];
        }

        return [[], [], null];
    }

    public function projectCard(array $project, string $locale): array
    {
        return [
            'type' => 'project', 'title' => $project['name'], 'subtitle' => $project['category'] ?: null,
            'text' => $project['text'], 'client' => $project['client'] ?: null, 'technologies' => $project['technologies'],
            'image' => $project['image'], 'href' => PagesResolver::href($locale, $project['path']),
        ];
    }

    /** Tokens plus the synonym concepts they contain. */
    public function withConcepts(array $vocab, array $synonyms): array
    {
        foreach ($synonyms as $group) {
            foreach ($group['terms'] as $term) {
                if (array_diff($term, array_keys($vocab)) === []) {
                    $vocab[$group['concept']] = true;
                    break;
                }
            }
        }

        return $vocab;
    }

    private function wordSet(array $words, callable $tokens): array
    {
        return array_fill_keys(array_merge(...array_map($tokens, $words ?: [''])), true);
    }

    private function unique(array $actions): array
    {
        return array_values(collect($actions)->unique('href')->all());
    }
}
