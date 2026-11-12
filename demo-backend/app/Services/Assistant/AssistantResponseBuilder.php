<?php

namespace App\Services\Assistant;

/**
 * Shapes engine decisions into the public response. Answer text is plain
 * text written by an admin or taken from CMS fields; the visitor's own words
 * are never echoed back.
 */
class AssistantResponseBuilder
{
    private const MAX_SUGGESTIONS = 4;

    /**
     * An admin question's answer. Lists (services, projects) show a few cards with a link to the
     * full page: as many as asked for ("two projects", "all services"), otherwise a short selection.
     *
     * @param  array{quantity?: int|string|null, topic?: ?array}  $options
     */
    public function matched(array $index, array $intent, ?string $entityKey, array $options = []): array
    {
        $answer = $intent['answer'];
        $cards = $intent['cards'];
        $actions = $intent['actions'];
        $quantity = $options['quantity'] ?? null;
        if ($intent['source_type'] === 'projects') {
            [$cards, $lead] = $this->projectCards($index, $entityKey, $options['topic'] ?? null, $quantity);
            $answer = $lead ?? $answer;
        }
        // "How much would it cost?" about a service or a project like one just discussed: the admin answer, framed.
        $about = array_values(array_filter(array_map(fn ($key) => $index['entities'][$key] ?? null, (array) ($options['about'] ?? []))));
        if ($about !== []) {
            $key = count($about) === 1 && $about[0]['type'] === 'project' ? 'assistant.public.about_project' : 'assistant.public.about_service';
            $answer = __($key, ['name' => $this->list(array_column($about, 'name'), $index['locale'])], $index['locale'])."\n\n".$answer;
            $actions = [...$actions, ...$this->serviceActions($about, $index)];
        }
        $total = count($cards);
        $cards = array_slice($cards, 0, $this->limit($quantity, $total));

        $suggestions = $this->suggestions($index, $intent['followups'], [$intent['id']]);
        if ($suggestions === []) {
            $suggestions = $this->suggestions($index, $index['suggested'], [$intent['id']], 3);
        }

        return $this->response('matched', $answer, ['id' => $intent['id'], 'question' => $intent['question']], $actions, $cards, $suggestions) + ['total' => $total];
    }

    /**
     * A service or project answered from the CMS. Capability ("Do you make mobile apps?"): yes, what
     * it is part of, the CMS description and an offer to discuss it. Information ("What is SEO & AI
     * Search Optimization?", "What is Treforma?"): what it is, from the CMS. The opening varies so
     * consecutive answers do not read alike; the facts never do.
     *
     * @param  string  $mode  capability | information
     */
    public function about(array $index, string $entityKey, string $mode, array $candidateIds = [], int $turn = 0, array $options = []): array
    {
        $entity = $index['entities'][$entityKey];
        $locale = $index['locale'];
        $text = $entity['card']['text'] ?? '';

        if ($entity['type'] === 'project') {
            $lead = __('assistant.public.entity_project', ['name' => $entity['name']], $locale);
            $actions = array_values(array_filter([$entity['action'], $index['contact_action']]));
            $suggestions = $this->suggestions($index, $candidateIds, [], 3) ?: $this->suggestions($index, $index['suggested'], [], 3);

            return $this->response('matched', trim($lead."\n\n".$text), null, $actions, [$entity['card']], $suggestions);
        }

        $sub = $entity['type'] === 'subservice';
        $replace = ['name' => $entity['name'], 'parent' => $entity['parent_name'] ?? '', 'company' => $this->company($index)];
        // After "I understand, you're looking for…" a second "Yes!" would sound repetitive: the plain statement instead.
        $acknowledged = $options['acknowledged'] ?? false;
        $set = ($mode === 'information' || $acknowledged ? 'info_' : 'capability_').($sub ? 'subservice' : 'service');
        $answer = $this->pick($index, $set, $entityKey.$turn, $replace);
        if ($mode === 'capability') {
            $answer .= ' '.$this->pick($index, 'capability_closing', $entityKey.$turn, $replace);
        }
        $children = array_column(array_filter($index['entities'], fn ($e) => ($e['parent'] ?? null) === $entityKey), 'name');
        // Part of a combined answer: the description stays on the card.
        $details = ($options['concise'] ?? false) ? '' : trim($text.($children ? ' '.__('assistant.public.includes', ['list' => $this->list($children, $locale)], $locale) : ''));

        $actions = [$this->named($sub ? ($index['entities'][$entity['parent']] ?? $entity) : $entity, $index), $index['booking_action'] ?? $index['contact_action']];
        $suggestions = $this->related($index, ['projects', 'pricing', 'timeline']);

        return $this->response('matched', trim($answer."\n\n".$details), null, array_values(array_filter($actions)), [$entity['card']], $suggestions);
    }

    /**
     * "Can you build something like Treforma?": a request for comparable work, not about the project.
     * Names the services the project's published type and description relate to, plus any the visitor
     * named ("a website like…"), offers to discuss it, and links the project and the service.
     *
     * @param  list<string>  $serviceKeys  services named in the question
     */
    public function similar(array $index, string $projectKey, array $serviceKeys = [], int $turn = 0): array
    {
        $project = $index['entities'][$projectKey];
        $areas = [];
        foreach ([...($project['services'] ?? []), ...$serviceKeys] as $key) {
            $family = $index['entities'][$key]['family'] ?? null;
            if ($family && isset($index['entities'][$family])) {
                $areas[$family] = $index['entities'][$family];
            }
        }
        $replace = ['project' => $project['name'], 'company' => $this->company($index), 'services' => $this->list(array_column($areas, 'name'), $index['locale'])];
        $answer = $areas !== [] ? $this->pick($index, 'similar', $projectKey.$turn, $replace) : __('assistant.public.similar_plain', $replace, $index['locale']);

        $actions = [
            $project['action'] ? ['label' => __('assistant.public.view_named', ['name' => $project['name']], $index['locale'])] + $project['action'] : null,
            ...$this->serviceActions(array_slice(array_values($areas), 0, 2), $index, plain: true),
            $this->relabel($index['booking_action'] ?? $index['contact_action'], __('assistant.public.start_project', [], $index['locale'])),
        ];

        return $this->response('matched', $answer, null, array_values(array_filter($actions)), array_slice(array_column(array_values($areas), 'card'), 0, 2),
            $this->related($index, ['pricing', 'timeline', 'projects']));
    }

    /**
     * A phrase an admin marked as a capability: confirmed ("yes"), related (the services it relates to,
     * and the team confirms the scope) or not offered. Never a promise beyond what the admin set.
     *
     * @param  array{status: string, term: string, entities: list<string>}  $capability
     */
    public function capability(array $index, array $capability): array
    {
        $locale = $index['locale'];
        $entities = array_values(array_filter(array_map(fn ($key) => $index['entities'][$key] ?? null, $capability['entities'])));
        $replace = ['term' => $capability['term'], 'company' => $this->company($index), 'services' => $this->offer($entities, $locale)];
        $key = match ($capability['status']) {
            'confirmed' => $entities ? 'capability_confirmed_services' : 'capability_confirmed',
            'related' => $entities ? 'capability_related' : 'capability_related_plain',
            default => 'capability_unsupported',
        };
        $families = [];
        foreach ($entities as $entity) {
            $family = $index['entities'][$entity['family'] ?? ''] ?? $entity;
            $families[$family['name']] = $family;
        }
        $actions = [
            ...$this->serviceActions(array_slice(array_values($families), 0, 2), $index),
            $this->relabel($index['booking_action'] ?? $index['contact_action'], __('assistant.public.talk_to_team', [], $locale)),
        ];
        $suggestions = $this->related($index, ['services', 'contact']);

        return $this->response('matched', __('assistant.public.'.$key, $replace, $locale), null, array_values(array_filter($actions)), [], $suggestions);
    }

    /**
     * Several requirements at once ("a website, hosting and SEO"): all of them in one answer, each with
     * its card, and one offer to plan them together. Joined by "or", the visitor picks where to start.
     *
     * @param  list<string>  $keys
     */
    public function several(array $index, array $keys, bool $alternatives = false, int $turn = 0): array
    {
        $entities = array_values(array_filter(array_map(fn ($key) => $index['entities'][$key] ?? null, $keys)));
        $replace = ['company' => $this->company($index), 'services' => $this->list(array_column($entities, 'name'), $index['locale'])];
        $answer = $alternatives ? __('assistant.public.several_choice', $replace, $index['locale']) : $this->pick($index, 'several', implode('|', $keys).$turn, $replace);
        $actions = [...$this->serviceActions($entities, $index), $index['booking_action'] ?? $index['contact_action']];

        return $this->response('matched', $answer, null, array_values(array_filter($actions)), array_slice(array_column($entities, 'card'), 0, 4),
            $this->related($index, ['pricing', 'timeline', 'projects']));
    }

    /**
     * A follow-up that refines the topic under discussion ("something simple", "for clothes instead"):
     * acknowledged and tied to the service it concerns, never with invented prices or timelines.
     */
    public function refine(array $index, string $entityKey, bool $scope = false, int $turn = 0): array
    {
        $entity = $index['entities'][$entityKey];
        // A project under discussion: the service it was built with.
        $service = $entity['type'] === 'project' ? ($index['entities'][$entity['services'][0] ?? ''] ?? null) : $entity;
        $answer = __($scope ? 'assistant.public.refine_scope' : 'assistant.public.refine', ['name' => $service['name'] ?? $entity['name']], $index['locale']);
        $actions = [...($service ? $this->serviceActions([$service], $index) : []), $index['booking_action'] ?? $index['contact_action']];

        return $this->response('matched', $answer, null, array_values(array_filter($actions)), [], $this->related($index, ['pricing', 'timeline', 'contact']));
    }

    /** Only something to leave out ("nuk dua marketing"): acknowledged, with a question about what is needed instead. */
    public function excluded(array $index, array $keys): array
    {
        $answer = __('assistant.public.excluded', ['names' => $this->families($index, $keys)], $index['locale']);

        return $this->response('matched', $answer, null, array_values(array_filter([$index['contact_action']])), [], $this->related($index, ['services', 'contact']));
    }

    /**
     * "I understand: you're looking for ERP & CRM Solutions rather than Digital Marketing & Growth."
     *
     * @param  list<string>  $wanted  services asked for instead, if any
     */
    public function noted(array $index, array $keys, array $wanted = []): string
    {
        $replace = ['names' => $this->families($index, $keys), 'wanted' => $this->families($index, $wanted)];

        return $wanted !== [] ? $this->pick($index, 'noted_instead', implode('|', [...$keys, ...$wanted]), $replace)
            : __('assistant.public.noted', $replace, $index['locale']);
    }

    /**
     * One question, several requests ("Can you build a shop like Treforma, and how much would it cost?"):
     * the main request answered first (the services or project concerned), then the admin's answer to the
     * pricing, timeline or getting-started part, then links: the services and project, then the quote,
     * meeting or contact. Nothing asked is dropped; nothing is added that the two answers do not say.
     */
    public function combine(array $index, array $primary, array $secondary, string $act): array
    {
        $answer = trim($primary['answer'])."\n\n".__('assistant.public.then_'.$act, [], $index['locale']).' '.trim($secondary['answer']);
        // Navigation for the subject first, then the request's own actions ("Request a quote"); one per destination.
        $requestHrefs = array_column($secondary['actions'], 'href');
        $navigation = array_filter($primary['actions'], fn ($action) => ! in_array($action['href'], $requestHrefs, true)
            && $action['href'] !== ($index['booking_action']['href'] ?? null) && $action['href'] !== ($index['contact_action']['href'] ?? null));
        $actions = [...$navigation, ...$secondary['actions']];
        $cards = array_slice([...$primary['cards'], ...array_filter($secondary['cards'], fn ($card) => $card['type'] === 'contact')], 0, 3);

        return $this->response('matched', $answer, $secondary['intent'], $actions, $cards, $secondary['suggestions'] ?: $primary['suggestions']);
    }

    /** "Would it work on mobile?" with nothing to go on: a website that works on phones, or an app? */
    public function mobileChoice(array $index, ?string $appKey): array
    {
        $app = $index['entities'][$appKey ?? ''] ?? null;
        $actions = $app ? $this->serviceActions([$app], $index) : [];
        $suggestions = $this->suggestions($index, $index['act_intents']['responsive'] ?? [], [], 1);

        return $this->response('ambiguous', __('assistant.public.mobile_which', [], $index['locale']), null, $actions, $app ? [$app['card']] : [], $suggestions);
    }

    /** Names of the given services; a subservice is left out when its main service is in the list too. */
    private function families(array $index, array $keys): string
    {
        $names = [];
        foreach ($keys as $key) {
            $entity = $index['entities'][$key] ?? null;
            if ($entity && ! in_array($entity['parent'] ?? null, $keys, true)) {
                $names[] = $entity['name'];
            }
        }

        return $this->list(array_values(array_unique($names)), $index['locale']);
    }

    /**
     * Links to services: one per page. Services shown on the same page (the services widget) share one
     * "Explore these services" link instead of several buttons with the same destination.
     */
    private function serviceActions(array $entities, array $index, bool $plain = false): array
    {
        $groups = [];
        foreach ($entities as $entity) {
            if ($entity['action'] ?? null) {
                $groups[$entity['action']['href']][] = $entity;
            }
        }
        $actions = [];
        foreach ($groups as $group) {
            $actions[] = count($group) === 1 ? $this->named($group[0], $index, $plain)
                : $this->relabel($group[0]['action'], __('assistant.public.explore_services', [], $index['locale']));
        }

        return array_values(array_filter($actions));
    }

    /** Several services fit equally well ("integrations"): the visitor picks one. */
    public function chooseService(array $index, array $entityKeys): array
    {
        $cards = array_map(fn ($key) => $index['entities'][$key]['card'], $entityKeys);

        return $this->response('ambiguous', __('assistant.public.which_service', [], $index['locale']), null, [], $cards, []);
    }

    /** Cards to show: the number asked for, else the short default; "all" shows every one. */
    private function limit(int|string|null $quantity, int $total): int
    {
        $cards = config('assistant.cards');
        if ($quantity === 'all') {
            return $total;
        }

        return is_int($quantity) ? max(1, min($quantity, (int) $cards['max'])) : (int) $cards['default'];
    }

    public function ambiguous(array $index, array $candidateIds): array
    {
        return $this->response('ambiguous', __('assistant.public.did_you_mean', [], $index['locale']), null, [], [],
            $this->suggestions($index, $candidateIds, [], (int) config('assistant.matching.max_candidates', 3)));
    }

    public function fallback(array $index, array $nearMissIds): array
    {
        $text = $index['profile']['fallback_message'] ?? null ?: __('assistant.public.fallback', [], $index['locale']);
        $suggestions = $this->suggestions($index, $nearMissIds, [], 3) ?: $this->suggestions($index, $index['suggested'], []);

        return $this->response('fallback', $text, null, array_values(array_filter([$index['contact_action']])), [], $suggestions);
    }

    /** @return list<array{id: int, question: string}> active questions in the given order */
    public function suggestions(array $index, array $ids, array $exclude = [], int $limit = self::MAX_SUGGESTIONS): array
    {
        $out = [];
        foreach ($ids as $id) {
            if (isset($index['intents'][$id]) && ! in_array($id, $exclude, true) && count($out) < $limit) {
                $out[] = ['id' => $id, 'question' => $index['intents'][$id]['question']];
            }
        }

        return $out;
    }

    /**
     * Projects to show, best first, and the lead-in for a narrowed list. In order: a topic the
     * visitor named ("ecommerce"), the service being discussed, otherwise all published projects.
     * Within a list, fuller entries (featured, with image and description) come first.
     *
     * @return array{0: list<array>, 1: ?string}
     */
    private function projectCards(array $index, ?string $entityKey, ?array $topic, int|string|null $quantity): array
    {
        $locale = $index['locale'];
        $projects = array_filter($index['entities'], fn ($entity) => $entity['type'] === 'project');
        $subject = $entityKey ? ($index['entities'][$entityKey] ?? null) : null;
        $lead = null;

        if ($topic && $topic['terms'] !== []) {
            $found = array_intersect_key($projects, array_flip($topic['keys']));
            if ($found !== []) {
                $projects = $found;
                $lead = $topic['label'] !== ''
                    ? __('assistant.public.projects_topic', ['topic' => $topic['label']], $locale)
                    : __('assistant.public.projects_matching', [], $locale);
            } else {
                // Said honestly, without repeating the visitor's words.
                $lead = __('assistant.public.projects_topic_none', [], $locale);
            }
        } elseif ($subject && $subject['type'] === 'project') {
            return [[$subject['card']], null];
        } elseif ($subject) {
            // Only words that set this service apart from the others ("ecommerce", not "solutions").
            $others = [];
            foreach ($index['entities'] as $key => $entity) {
                if ($key !== $entityKey && $entity['type'] !== 'project') {
                    $others += array_fill_keys($entity['tokens'], true);
                }
            }
            $distinct = array_diff($subject['tokens'], array_keys($others));
            $related = array_filter($projects, fn ($project) => $distinct !== [] && array_intersect($distinct, $project['related']) !== []);
            if ($related !== []) {
                $projects = $related;
            }
        }

        $order = array_flip(array_keys($projects));
        uksort($projects, fn ($a, $b) => [-($projects[$a]['quality'] ?? 0), $order[$a]] <=> [-($projects[$b]['quality'] ?? 0), $order[$b]]);
        if ($lead === null && $quantity !== null && $quantity !== 'all') {
            $count = min((int) $quantity, count($projects));
            $lead = $count === 1 ? __('assistant.public.projects_one', [], $locale) : __('assistant.public.projects_count', ['count' => $count], $locale);
        }

        return [array_values(array_map(fn ($project) => $project['card'], $projects)), $lead];
    }

    /**
     * One of several wordings for the same statement, chosen by a stable seed (the subject and turn),
     * so answers stay reproducible while consecutive ones read differently.
     */
    private function pick(array $index, string $set, string $seed, array $replace): string
    {
        // Wordings an admin wrote for this language (General settings → Response wording) replace the built-in ones.
        $custom = $index['profile']['templates'][$set] ?? [];
        if ($custom !== []) {
            $pairs = [];
            foreach ($replace as $key => $value) {
                $pairs[':'.$key] = (string) $value;
            }
            // Longer placeholders first: ":names" before ":name".
            uksort($pairs, fn ($x, $y) => strlen($y) <=> strlen($x));

            return strtr($custom[crc32($seed) % count($custom)], $pairs);
        }
        $options = trans('assistant.public.'.$set, [], $index['locale']);
        $count = is_array($options) ? count($options) : 0;
        if ($count === 0) {
            return __('assistant.public.'.$set, $replace, $index['locale']);
        }

        return __('assistant.public.'.$set.'.'.(crc32($seed) % $count), $replace, $index['locale']);
    }

    /** "A", "A and B", "A, B and C" in the answer's language. */
    private function list(array $items, string $locale): string
    {
        $items = array_values(array_unique($items));
        $last = array_pop($items);

        return $items === [] ? (string) $last : implode(', ', $items).' '.__('assistant.public.and', [], $locale).' '.$last;
    }

    /** "Digital Marketing & Growth, including Social Media & Content Marketing": linked services grouped by their main service. */
    private function offer(array $entities, string $locale): string
    {
        $groups = [];
        foreach ($entities as $entity) {
            $parent = $entity['parent_name'] ?? null;
            $groups[$parent ?? $entity['name']] ??= [];
            if ($parent) {
                $groups[$parent][] = $entity['name'];
            }
        }
        $parts = [];
        foreach ($groups as $service => $subs) {
            $parts[] = $subs ? __('assistant.public.service_including', ['service' => $service, 'list' => $this->list($subs, $locale)], $locale) : $service;
        }

        return $this->list($parts, $locale);
    }

    private function company(array $index): string
    {
        return $index['company'] ?? (string) config('app.name');
    }

    /** A link to a service or project labelled with its name: "Explore Software Development", or just "E-commerce Solutions". */
    private function named(array $entity, array $index, bool $plain = false): ?array
    {
        if (! $entity['action']) {
            return null;
        }
        $label = $plain ? $entity['name'] : __($entity['type'] === 'project' ? 'assistant.public.view_named' : 'assistant.public.explore_named', ['name' => $entity['name']], $index['locale']);

        return ['label' => Resolvers\Text::plain($label, 120)] + $entity['action'];
    }

    private function relabel(?array $action, string $label): ?array
    {
        return $action ? ['label' => $label] + $action : null;
    }

    /** Follow-up questions for a composed answer: the admin's questions about examples, pricing, timelines… */
    private function related(array $index, array $kinds): array
    {
        $ids = [];
        foreach ($kinds as $kind) {
            if ($id = $index['act_intents'][$kind][0] ?? null) {
                $ids[] = $id;
            }
        }

        return $this->suggestions($index, array_values(array_unique($ids)), [], 3);
    }

    private function unique(array $actions): array
    {
        return array_values(collect(array_filter($actions))->unique('href')->all());
    }

    private function response(string $status, string $answer, ?array $intent, array $actions, array $cards, array $suggestions): array
    {
        return [
            'status' => $status,
            'answer' => $answer,
            'intent' => $intent,
            // One button per destination: the first (most specific) wins.
            'actions' => $this->unique($actions),
            'cards' => array_values($cards),
            'suggestions' => $suggestions,
        ];
    }
}
