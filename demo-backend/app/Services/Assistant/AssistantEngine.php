<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Resolvers\PagesResolver;

/**
 * The assistant: one entry point shared by the public API and the admin
 * test console. Fully deterministic: no external AI service is called and a
 * visitor's message is only ever matched, never followed as an instruction.
 *
 * A question is read in three parts: its language (the answer follows the
 * visitor, not the page), what it asks for (pricing, a timeline, getting in
 * touch, something similar to a project, projects, information, a
 * capability) and what it is about (the services, subservices and projects
 * it names). An explicit request wins over a name: "How much would a shop
 * like Treforma cost?" is answered with the pricing answer, not Treforma's
 * description.
 *
 * Outcomes:
 *  - matched: a confident answer (an admin question, or one composed from CMS services/projects),
 *  - ambiguous: several plausible questions or services, offered as choices,
 *  - fallback: nothing reliable; the fallback text, related questions and contact link.
 */
class AssistantEngine
{
    public const ENTITY_PATTERN = '/^(service|subservice|project):[A-Za-z0-9_:\/-]{1,180}$/';

    public const LANGUAGE_PATTERN = '/^[a-z]{2,3}$/';

    /** Requests a conversation can carry over to the next question ("what about mobile?"). */
    public const ACTS = AssistantRequestClassifier::ACTS;

    public function __construct(
        private AssistantIndexRepository $indexes,
        private AssistantMatcher $matcher,
        private AssistantResponseBuilder $responses,
        private UnansweredQuestionLogger $logger,
        private AssistantNormalizer $normalizer,
        private AssistantLanguageDetector $detector,
        private AssistantRequestClassifier $classifier,
    ) {}

    public function enabled(int $channelId, string $locale): bool
    {
        return (bool) ($this->indexes->get($channelId, $locale)['profile']['enabled'] ?? false);
    }

    /**
     * Public configuration for the chat window.
     *
     * @param  bool  $preview  a trusted preview request: configuration is returned while the assistant is still hidden
     */
    public function bootstrap(int $channelId, string $locale, bool $preview = false): array
    {
        $index = $this->indexes->get($channelId, $locale);
        $profile = $index['profile'] ?? [];
        $enabled = (bool) ($profile['enabled'] ?? false);
        if (! $enabled && ! $preview) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'preview' => ! $enabled,
            'show_on_mobile' => (bool) ($profile['show_on_mobile'] ?? true),
            'name' => $profile['name'] ?? __('assistant.public.default_name', [], $locale),
            'welcome_message' => $profile['welcome_message'] ?? __('assistant.public.default_welcome', [], $locale),
            'tooltip' => $profile['tooltip'] ?? __('assistant.public.default_tooltip', [], $locale),
            'placeholder' => $profile['placeholder'] ?? __('assistant.public.default_placeholder', [], $locale),
            'notice' => $profile['notice'] ?? __('assistant.public.default_notice', [], $locale),
            'suggestions' => $this->responses->suggestions($index, $index['suggested'], [], 6),
            'max_length' => (int) config('assistant.max_length', 300),
        ];
    }

    /**
     * Answer one message.
     *
     * @param  string  $locale  the site's language: links always point to pages in it
     * @param  array{intent_id?: ?int, entity?: ?string, turn?: ?int, language?: ?string, act?: ?string}  $context  what the previous answer returned
     * @param  ?int  $intentId  a suggestion the visitor clicked, answered directly
     * @param  bool  $debug  include matching details (admin test console); never logs
     * @param  bool  $log  record unanswered questions (off for preview traffic)
     */
    public function reply(int $channelId, string $locale, string $message, array $context = [], ?int $intentId = null, bool $debug = false, bool $log = true): array
    {
        $site = $this->indexes->get($channelId, $locale);
        [$index, $language] = $this->conversationIndex($channelId, $locale, $site, $message, $context, $intentId);
        $context = $this->context($context, $index);
        $query = $this->matcher->analyze($message, $index);
        $subjects = [];
        $request = ['act' => null, 'acts' => [], 'reference' => false, 'ellipsis' => false];
        $entity = null;
        $ranked = [];
        $excluded = [];
        $requirements = [];
        $clauses = ['words' => [], 'negation' => false, 'correction' => false, 'alternative' => false];

        if ($intentId !== null && isset($index['intents'][$intentId])) {
            $decision = ['matched', $intentId, 'suggestion'];
        } elseif ($this->normalizer->isContinuation($query['text'], $index['locale']) && ($continued = $this->continuation($index, $context))) {
            $decision = $continued;
        } else {
            // "Not marketing, an ERP system": services named only in negative clauses are excluded, and
            // the rest of the question is read without them.
            $clauses = $this->classifier->clauses($message, $index['language']);
            [$query, $excluded] = $this->affirmative($query, $index, $clauses);
            $subjects = array_values(array_filter($this->matcher->subjects($query, $index), fn ($s) => ! in_array($s['key'], $excluded, true)));
            $request = $this->classifier->classify($query['words'], $index['language'], $this->names($subjects, $index));
            // Description words name a service only in an explicit request ("can you manage our servers?").
            if (! in_array($request['act'], ['capability', 'information'], true)) {
                $subjects = array_values(array_filter($subjects, fn ($s) => $s['via'] !== 'description'));
            }
            // "Something simple, not too complex": a refinement only when nothing else unknown is said.
            $refineWords = (array) config('assistant.refine.'.$index['language'], []);
            $generic = $index['entity_words']['service'] ?? [];
            $unknown = array_filter($query['tokens'], fn ($token, $position) => ! isset($generic[$token])
                && ! in_array($query['words'][$query['origins'][$position] ?? -1] ?? '', $refineWords, true), ARRAY_FILTER_USE_BOTH);
            $request['refine'] = $this->mentions($query['words'], $refineWords) && $unknown === [];
            $request['responsive'] = $this->mentions($query['words'], (array) config('assistant.responsive.'.$index['language'], []));
            $request += ['negation' => $clauses['negation'], 'correction' => $clauses['correction']];
            // "A website, hosting and SEO", "an app that connects to our ERP": every requirement, not one winner.
            $requirements = $this->requirements($subjects, $query, $index, $clauses);
            // "What about mobile?": only the subject changes, the previous request carries over.
            if ($request['act'] === null && $request['ellipsis'] && $context['act']) {
                $request['act'] = $context['act'];
            }
            $entity = $this->matcher->entity($query, $index, $subjects);
            $ranked = $this->matcher->rank($query, $index, $entity['key'] ?? null, $context);
            $decision = $this->decide($ranked, $entity);
            $decision = $this->preferProjects($decision, $query, $index, $ranked);
            $decision = $this->narrowPreviousList($decision, $query, $index, $context);
            // A project named outright ("tell me about Treforma") gets its own answer, not a list.
            if ($entity && $index['entities'][$entity['key']]['type'] === 'project' && $decision[0] === 'matched'
                && in_array($decision[2], ['confident', 'context_topic'], true) && ($index['intents'][$decision[1]]['source_type'] ?? null) === 'projects') {
                $decision = ['matched', $entity['key'], 'entity'];
            }
            $decision = $this->understand($decision, $request, $query, $index, $ranked, $subjects, $context, $requirements, $excluded);
        }

        [$status, $target, $reason] = $decision;
        $entityKey = $entity['key'] ?? null;
        $config = config('assistant.matching');
        $candidates = array_column(array_filter($ranked, fn ($r) => $r['score'] >= $config['candidate']), 'id');
        $strong = array_values(array_filter($subjects, fn ($s) => $s['strong']));
        $act = $request['act'];
        $answered = null;
        $entities = [];
        $general = false;

        if ($status === 'matched' && in_array($reason, ['several', 'alternatives'], true)) {
            $response = $this->responses->several($index, $target, $reason === 'alternatives', $context['turn']);
            $entities = $target;
            $entityKey = $target[0];
            $answered = 'capability';
        } elseif ($status === 'matched' && $reason === 'refine') {
            $response = $this->responses->refine($index, $target, $request['refine'], $context['turn']);
            $entityKey = $target;
            $entities = $context['entities'];
            $answered = $context['act'] ?? 'capability';
        } elseif ($status === 'matched' && $reason === 'excluded') {
            $response = $this->responses->excluded($index, $target);
            $entityKey = null;
        } elseif ($status === 'ambiguous' && $reason === 'mobile') {
            $response = $this->responses->mobileChoice($index, $strong[0]['key'] ?? null);
            $entityKey = $context['entity'];
        } elseif ($status === 'matched' && in_array($reason, ['entity', 'capability', 'information'], true)) {
            $type = $index['entities'][$target]['type'];
            $mode = $reason === 'entity' ? ($type === 'project' || $act === 'information' ? 'information' : 'capability') : $reason;
            $response = $this->responses->about($index, $target, $mode, $candidates, $context['turn'], ['acknowledged' => $excluded !== []]);
            $entityKey = $target;
            $answered = $type === 'project' ? 'information' : $mode;
        } elseif ($status === 'matched' && $reason === 'similar') {
            $services = array_column(array_filter($strong, fn ($s) => $s['type'] !== 'project'), 'key');
            $response = $this->responses->similar($index, $target, $services, $context['turn']);
            $entityKey = $target;
            $answered = 'similar';
        } elseif ($status === 'matched' && $reason === 'capability_phrase') {
            $response = $this->responses->capability($index, $target);
            $entityKey = $target['entities'][0] ?? $context['entity'];
            $answered = 'capability';
        } elseif ($status === 'ambiguous' && $reason === 'subjects') {
            $response = $this->responses->chooseService($index, $target);
            $entityKey = $context['entity'];
        } elseif ($status === 'matched') {
            $intent = $index['intents'][$target];
            $entityKey ??= $intent['entity'] ?? $context['entity'];
            // A project from an earlier answer does not narrow a new list ("show me two projects");
            // a service under discussion still does ("do you have examples?").
            if ($intent['source_type'] === 'projects' && ! isset($entity['key']) && ($index['entities'][$entityKey ?? '']['type'] ?? null) === 'project') {
                $entityKey = null;
            }
            // List answers honour "one", "two", "all" and a named topic ("ecommerce projects");
            // "tell me more" after a list shows all of it.
            $topic = $intent['source_type'] === 'projects' ? $this->projectsTopic($query, $index, $entityKey, $intent) : null;
            $quantity = $reason === 'expand' ? 'all' : $query['quantity'];
            // Pricing and timelines about something named now or just discussed: "how much would it cost?".
            $about = null;
            if (in_array($act, ['pricing', 'timeline'], true) && ($reason === 'act' || in_array($target, $index['act_intents'][$act] ?? [], true))) {
                // The subject just discussed applies only when the question names nothing else ("how much would it cost?").
                $carried = $context['act'] && $context['act'] !== 'projects' && $this->matcher->coverageWith($query, $intent, $this->cuePositions($query, $index, $act)) >= 1.0
                    ? ($context['entities'] ?: array_filter([$context['entity']])) : [];
                $named = array_column(array_filter($strong, fn ($s) => $s['positions'] !== []), 'key');
                $about = $named !== [] ? array_slice($named, 0, 1) : array_values($carried);
                if (count($requirements['keys'] ?? []) > 1) {
                    $about = $requirements['keys'];
                }
                $entities = $about;
                $entityKey = $about[0] ?? $entityKey;
            } elseif ($reason === 'responsive') {
                // "Would it also work on mobile?" about the site under discussion.
                $about = array_values(array_filter([$context['entity']]));
                $entityKey = $context['entity'];
                $entities = $context['entities'];
            }
            // "Can you build a shop like Treforma, and how much would it cost?": the request about the services or
            // project comes first, then this answer about pricing, timing or getting started.
            $primary = $reason === 'act' ? $this->primaryRequest($request, $index, $strong, $requirements, $context['turn']) : null;
            if ($primary) {
                $response = $this->responses->combine($index, $primary['response'],
                    $this->responses->matched($index, $intent, $primary['entity'], ['quantity' => $quantity]), $act);
                $entityKey = $primary['entity'];
                $entities = $primary['entities'];
            } else {
                $response = $this->responses->matched($index, $intent, $entityKey, ['quantity' => $quantity, 'topic' => $topic,
                    'about' => $about]);
            }
            // One project shown: it becomes what "tell me more" refers to.
            if ($intent['source_type'] === 'projects' && count($response['cards']) === 1) {
                $entityKey = $this->entityForCard($index, $response['cards'][0]) ?? $entityKey;
            }
            // The request answered, also when the admin's wording matched outright ("Sa mund të kushtojë?").
            $asked = $act && in_array($target, $index['act_intents'][$act] ?? [], true);
            // An answer not tied to any service or list ("will it work on phones?").
            $general = $intent['source_type'] === null && $intent['entity'] === null && $reason !== 'suggestion';
            // "Would it work on mobile?" is about the site under discussion, not about mobile apps.
            if (in_array($target, $index['act_intents']['responsive'] ?? [], true) && $context['entity']) {
                $entityKey = $context['entity'];
            }
            $answered = $reason === 'act' || $asked ? $act : ($reason === 'responsive' ? $context['act'] : ($intent['source_type'] === 'projects' ? 'projects' : null));
            // A request about what was discussed keeps its subject for the next question.
            if ($reason === 'act' && $act === 'contact' && $context['entity']) {
                $entityKey = $context['entity'];
            }
        } elseif ($status === 'ambiguous') {
            $response = $this->responses->ambiguous($index, $target);
        } else {
            $nearMisses = array_column(array_filter($ranked, fn ($r) => $r['score'] >= $config['candidate'] / 2), 'id');
            $response = $this->responses->fallback($index, $nearMisses);
            if (! $debug && $log && $query['tokens'] !== [] && ($index['profile']['log_unanswered'] ?? true)) {
                $this->logger->record($channelId, $index['locale'], $message);
            }
        }

        // "Understood, not Digital Marketing & Growth." before an answer that leaves something out.
        if ($excluded !== [] && $status === 'matched' && $reason !== 'excluded') {
            $wanted = array_values(array_filter($entities ?: [$entityKey], fn ($key) => $key && ($index['entities'][$key]['type'] ?? null) !== 'project'));
            $response['answer'] = $this->responses->noted($index, $excluded, $reason === 'refine' ? [] : $wanted)."\n\n".$response['answer'];
        }

        $turn = $context['turn'] + 1;
        $subjectKey = $status === 'matched' ? ($entityKey ?? $context['entity']) : $context['entity'];
        $mentioned = [...array_column($strong, 'key'), ...$entities];
        $response['context'] = [
            'intent_id' => $status === 'matched' ? ($response['intent']['id'] ?? null) : $context['intent_id'],
            'entity' => in_array($subjectKey, $excluded, true) ? null : $subjectKey,
            // Several services under discussion ("a website and social media"), and services the visitor ruled out.
            'entities' => $status === 'matched' ? array_values(array_slice($entities, 0, 4)) : $context['entities'],
            'excluded' => array_values(array_slice(array_diff(array_unique([...$excluded, ...$context['excluded']]), $mentioned), 0, 5)),
            'turn' => $turn,
            'language' => $index['language'],
            // A general answer that names nothing new ("will it work on phones?") keeps the request under discussion.
            'act' => $status === 'matched' ? ($answered ?? ($general ? $context['act'] : null)) : $context['act'],
        ];
        // Long conversations drift; start the topic afresh after a while.
        if ($turn >= 20) {
            $response['context'] = ['intent_id' => null, 'entity' => null, 'entities' => [], 'excluded' => [], 'turn' => 0, 'language' => $index['language'], 'act' => null];
        }
        $response['language'] = $index['language'];
        // An answer in another language than the page still links to pages in the page's language.
        if ($index['locale'] !== $locale) {
            $response = $this->relocate($response, $index['locale'], $locale);
        }

        if ($debug) {
            $response['debug'] = $this->debug($query, $index, $ranked, $entity, $reason) + [
                'quantity' => $query['quantity'], 'topic' => $topic ?? null, 'language' => $language, 'request' => $request,
                'excluded' => array_map(fn ($key) => $index['entities'][$key]['name'], $excluded),
                'requirements' => array_map(fn ($key) => $index['entities'][$key]['name'], $requirements['keys'] ?? []),
                'corrected' => array_map(fn ($original, $position) => $original.' → '.$query['tokens'][$position], $query['corrected'], array_keys($query['corrected'])),
                'subjects' => array_map(fn ($s) => ['name' => $index['entities'][$s['key']]['name'], 'type' => $s['type'], 'score' => $s['score'],
                    'coverage' => $s['coverage'], 'via' => $s['via'], 'strong' => $s['strong']], array_slice($subjects, 0, 5)),
            ];
        }

        return $response;
    }

    /**
     * The index to answer from: the question's language when it is clear, otherwise the
     * conversation's previous language, otherwise the site's.
     *
     * @return array{0: array, 1: array{language: ?string, scores: array<string, float>, used: string}}
     */
    private function conversationIndex(int $channelId, string $locale, array $site, string $message, array $context, ?int $intentId): array
    {
        $codes = [];
        foreach ($this->indexes->locales() as $code) {
            $codes[$this->normalizer->language($code)] ??= $code;
        }
        $siteLanguage = $this->normalizer->language($locale);
        $codes[$siteLanguage] = $locale;
        $index = fn (string $language) => $codes[$language] === $locale ? $site : $this->indexes->get($channelId, $codes[$language]);

        $detection = ['language' => null, 'scores' => []];
        if ($intentId === null && count($codes) > 1) {
            $lexicons = [];
            foreach (array_keys($codes) as $language) {
                $lexicons[$language] = $index($language)['lexicon'] ?? [];
            }
            $detection = $this->detector->detect($message, $lexicons);
        }
        $previous = is_string($context['language'] ?? null) ? $context['language'] : null;
        $language = match (true) {
            isset($codes[$detection['language'] ?? '']) => $detection['language'],
            isset($codes[$previous ?? '']) => $previous,
            default => $siteLanguage,
        };

        return [$index($language), $detection + ['used' => $language]];
    }

    /** Links built for the answer's language, moved to the page's language ("/sq/services" → "/en/services"). */
    private function relocate(array $response, string $from, string $to): array
    {
        foreach (['actions', 'cards'] as $list) {
            foreach ($response[$list] as $i => $item) {
                if (isset($item['href']) && is_string($item['href'])) {
                    $response[$list][$i]['href'] = PagesResolver::relocate($item['href'], $from, $to);
                }
            }
        }

        return $response;
    }

    /**
     * Explicit requests over names. In order: the admin's exact wording; a website working on
     * phones; pricing, timelines and getting in touch (answered by the admin question for that
     * request, about whatever was named); "something like Treforma"; phrases an admin marked as
     * related or not offered ("logo"); projects by topic; several requirements at once ("a website,
     * hosting and SEO"); a follow-up that refines the current topic ("something simple"); then the
     * named service or project itself, or a choice when several services fit equally well.
     */
    private function understand(array $decision, array $request, array $query, array $index, array $ranked, array $subjects, array $context, array $requirements, array $excluded): array
    {
        if (in_array($decision[2], ['exact', 'context_topic'], true)) {
            return $decision;
        }
        $act = $request['act'];
        $strong = array_values(array_filter($subjects, fn ($s) => $s['strong']));
        $top = $strong[0] ?? null;
        $project = collect($strong)->firstWhere('type', 'project');
        // "that", "it", or nothing named: the request is about what was just discussed.
        $referenced = ($request['reference'] || $strong === []) ? $context['entity'] : null;

        // "Would it also work on mobile?": about a website, the site on phones; otherwise ask which is meant.
        if ($request['responsive'] && ! $this->mentionsApp($query)) {
            $web = $this->isWeb($context['entity'], $index) || array_filter($strong, fn ($s) => $this->isWeb($s['key'], $index)) !== []
                || array_intersect($query['tokens'], (array) config('assistant.web_words', [])) !== [];
            $answer = $index['act_intents']['responsive'][0] ?? null;

            return $web && $answer ? ['matched', $answer, 'responsive'] : ['ambiguous', null, 'mobile'];
        }

        if (in_array($act, ['pricing', 'timeline', 'contact'], true) && ($found = $this->actIntent($act, $query, $index, $ranked, $strong))) {
            return ['matched', $found, 'act'];
        }

        if ($act === 'similar' && ($target = $project['key'] ?? $referenced ?? ($top['key'] ?? null))) {
            return ['matched', $target, ($index['entities'][$target]['type'] ?? null) === 'project' ? 'similar' : 'capability'];
        }

        $capability = $this->matcher->capability($query, $index);
        $confident = $decision[0] === 'matched' && $decision[2] === 'confident' && ($index['intents'][$decision[1]]['source_type'] ?? null) !== 'services';
        if ($capability && ! $confident && count($capability['positions']) >= count($top['positions'] ?? [])) {
            return ['matched', $capability, 'capability_phrase'];
        }

        if ($act === 'projects' && ! $project && ($decision[0] !== 'matched' || ($index['intents'][$decision[1]]['source_type'] ?? null) !== 'projects')) {
            $listing = collect($ranked)->first(fn ($r) => ($index['intents'][$r['id']]['source_type'] ?? null) === 'projects')['id'] ?? ($index['act_intents']['projects'][0] ?? null);
            $topic = $listing ? $this->matcher->projectTopic($query, $index, null, $index['intents'][$listing]['vocab']) : null;
            // A list was asked for: never a service description instead ("two projects related to software development").
            // Unknown words ("the Secret Prototype project") are not a topic: no list then.
            if ($listing && ($topic['keys'] !== [] || $top)) {
                return ['matched', $listing, 'confident'];
            }
        }

        // Several independent requirements: all of them, or the alternatives when joined by "or".
        if ($requirements !== [] && ! $confident) {
            return ['matched', $requirements['keys'], $requirements['alternative'] ? 'alternatives' : 'several'];
        }

        // "Do you offer custom technology?": a service named in full beats a general answer that only shares a word with it.
        $general = $confident ? collect($ranked)->firstWhere('id', $decision[1]) : null;
        if ($general && $top && $act === 'capability' && $top['score'] >= 1.0 && $top['coverage'] >= 1.0
            && $index['intents'][$decision[1]]['entity'] === null && $general['signals']['coverage'] < 1.0) {
            return ['matched', $top['key'], $top['type'] === 'project' ? 'information' : 'capability'];
        }
        // A confident admin answer stays, except a general services list when one service was named ("do you offer cloud services?").
        if ($confident || ($decision[0] === 'matched' && $decision[2] !== 'entity' && ! $top)) {
            return $decision;
        }

        // Nothing new named: "something simple", "for clothes instead of 3D products", "not that one" refine the current topic.
        $current = $context['entity'] && ! in_array($context['entity'], $excluded, true) ? $context['entity'] : null;
        if (! $top && $current && ($request['refine'] || $excluded !== [] || $request['correction'])) {
            return ['matched', $current, 'refine'];
        }
        if (! $top && $excluded !== []) {
            return ['matched', $excluded, 'excluded'];
        }

        if ($top && ($choices = $this->closeServices($strong, $subjects)) !== []) {
            return ['ambiguous', $choices, 'subjects'];
        }
        // Only a word several areas share ("Do you do integrations?"): the visitor picks the area.
        if (! $top && in_array($act, ['capability', 'information'], true)) {
            $weak = array_values(array_filter($subjects, fn ($s) => ! $s['strong'] && ! $s['fuzzy'] && $s['explained'] > 0 && $s['type'] !== 'project'));
            $choices = [];
            foreach ($weak as $subject) {
                $choices[$subject['family']] ??= $subject['key'];
            }
            if (count($choices) >= 2 && $decision[0] !== 'matched') {
                return ['ambiguous', array_slice(array_values($choices), 0, 3), 'subjects'];
            }
        }
        // The question is about the service when its other words are the usual ones around a name, or, for an
        // explicit request ("I need backups for my office servers"), when most of them are.
        $covered = array_values(array_unique(array_merge(...array_map(fn ($s) => $s['positions'], $strong) ?: [[]])));
        // Words the CMS uses to describe the same service area count too: "office servers" for backups (Cloud & ICT).
        if ($top && $top['type'] !== 'project') {
            $area = array_filter($index['entities'], fn ($e) => ($e['family'] ?? null) === $top['family']);
            foreach ($query['tokens'] as $position => $token) {
                if (array_filter($area, fn ($e) => isset($e['desc'][$token]))) {
                    $covered[] = $position;
                }
            }
            $covered = array_values(array_unique($covered));
        }
        $coverage = $top ? max($top['coverage'], $query['tokens'] ? (count($covered) + $this->genericCount($query, $index, $covered)) / count($query['tokens']) : 0) : 0;
        if ($top && $coverage >= (in_array($act, ['capability', 'information'], true) ? 0.5 : 0.6)) {
            $mode = $top['type'] === 'project' || $act === 'information' ? 'information' : 'capability';

            return ['matched', $top['key'], $mode];
        }
        // "Do you do that?", "Can you build it?": the subject of the previous answer.
        if ($referenced && $request['reference'] && in_array($act, ['capability', 'information'], true) && isset($index['entities'][$referenced])) {
            $type = $index['entities'][$referenced]['type'];

            return ['matched', $referenced, $type === 'project' ? ($act === 'capability' ? 'similar' : 'information') : $act];
        }

        return $decision;
    }

    /**
     * The main request of a combined question: a capability ("can you build…", "a boni…") about services or a
     * project the question names. Null when the question is only about pricing, timing or getting started.
     *
     * @return array{response: array, entity: string, entities: list<string>}|null
     */
    private function primaryRequest(array $request, array $index, array $strong, array $requirements, int $turn): ?array
    {
        if (! in_array('capability', $request['acts'], true)) {
            return null;
        }
        $project = collect($strong)->firstWhere('type', 'project');
        $services = array_values(array_column(array_filter($strong, fn ($s) => $s['type'] !== 'project' && $s['positions'] !== []), 'key'));
        if ($project && in_array('similar', $request['acts'], true)) {
            return ['response' => $this->responses->similar($index, $project['key'], $services, $turn), 'entity' => $project['key'], 'entities' => [$project['key']]];
        }
        if (count($requirements['keys'] ?? []) > 1) {
            return ['response' => $this->responses->several($index, $requirements['keys'], false, $turn), 'entity' => $requirements['keys'][0], 'entities' => $requirements['keys']];
        }
        if ($services !== []) {
            return ['response' => $this->responses->about($index, $services[0], 'capability', [], $turn, ['concise' => true]), 'entity' => $services[0], 'entities' => [$services[0]]];
        }

        return null;
    }

    /**
     * The admin question that answers a pricing, timeline or contact request: worded for that request
     * and accounting for the question once the services and projects it names, the usual words around
     * them and the request's own words ("request a quote") are set aside.
     */
    private function actIntent(string $act, array $query, array $index, array $ranked, array $subjects): ?int
    {
        $ids = $index['act_intents'][$act] ?? [];
        $generic = ($index['entity_words']['service'] ?? []) + ($index['entity_words']['project'] ?? []);
        $covered = array_merge(
            array_keys(array_filter($query['tokens'], fn ($token) => isset($generic[$token]))),
            $this->cuePositions($query, $index, $act),
            ...array_column($subjects, 'positions'),
        );
        // Best-scoring first; "and Google Ads?" after a pricing answer names only a service, so any pricing question may answer it.
        $order = array_values(array_unique([...array_column($ranked, 'id'), ...$ids]));
        foreach ($order as $id) {
            if (in_array($id, $ids, true) && $this->matcher->coverageWith($query, $index['intents'][$id], $covered) >= 0.6) {
                return $id;
            }
        }

        return null;
    }

    /** Token positions of the request's own words ("kohë" in "sa kohë zgjat?", "quote" in "request a quote"). */
    private function cuePositions(array $query, array $index, string $act): array
    {
        $cueWords = array_fill_keys(explode(' ', implode(' ', (array) config('assistant.acts.'.$index['language'].'.'.$act, []))), true);

        return array_keys(array_filter($query['origins'] ?? [], fn ($word) => isset($cueWords[$query['words'][$word] ?? ''])));
    }

    /**
     * The question without its negative clauses, and the services named only there ("nuk dua marketing").
     *
     * @return array{0: array, 1: list<string>}
     */
    private function affirmative(array $query, array $index, array $clauses): array
    {
        $meta = $clauses['words'];
        if (! $clauses['negation'] || count($meta) !== count($query['words'])) {
            return [$query, []];
        }
        $negative = array_keys(array_filter($query['origins'], fn ($word) => $meta[$word]['negative'] ?? false));
        if ($negative === []) {
            return [$query, []];
        }
        $excluded = [];
        foreach ($this->matcher->subjects($query, $index) as $subject) {
            if ($subject['strong'] && $subject['positions'] !== [] && array_diff($subject['positions'], $negative) === []) {
                $excluded[] = $subject['key'];
            }
        }

        return [$this->matcher->restrict($query, $index, array_values(array_diff(array_keys($query['tokens']), $negative))), array_values(array_unique($excluded))];
    }

    /**
     * Independent requirements: the best service of each clause ("a website, hosting and SEO"), and,
     * when the question connects things ("an app that connects to our ERP"), the other services it
     * names in the same clause from other areas. A main service gives way to its own subservice.
     *
     * @return array{keys: list<string>, alternative: bool}|array{}
     */
    private function requirements(array $subjects, array $query, array $index, array $clauses): array
    {
        $services = array_values(array_filter($subjects, fn ($s) => $s['strong'] && $s['type'] !== 'project' && $s['positions'] !== []));
        if (count($services) < 2) {
            return [];
        }
        $clauseOf = fn (array $s) => $clauses['words'][$query['origins'][$s['positions'][0]] ?? -1]['clause'] ?? 0;
        $chosen = [];
        foreach ($services as $service) {
            $chosen[$clauseOf($service)] ??= $service;
        }
        if ($this->mentions($query['words'], (array) config('assistant.integration.'.$index['language'], []))) {
            // First the things being connected (named by distinctive words), then, if there are at least
            // two, the connecting itself ("connect", "lidhet": Custom Integrations & APIs).
            foreach ([true, false] as $distinctive) {
                if (! $distinctive && count($chosen) < 2) {
                    break;
                }
                foreach ($services as $service) {
                    $taken = array_merge(...array_map(fn ($s) => $s['positions'], array_values($chosen)));
                    if (($service['explained'] > 0) === $distinctive && ! in_array($service['family'], array_column($chosen, 'family'), true)
                        && array_intersect($service['positions'], $taken) === []) {
                        $chosen[] = $service;
                    }
                }
            }
        }
        // In the order the visitor named them.
        usort($chosen, fn ($a, $b) => min(array_map(fn ($p) => $query['origins'][$p] ?? 0, $a['positions'])) <=> min(array_map(fn ($p) => $query['origins'][$p] ?? 0, $b['positions'])));
        $keys = array_values(array_unique(array_column($chosen, 'key')));
        $keys = array_values(array_filter($keys, fn ($key) => ! array_filter($keys, fn ($other) => ($index['entities'][$other]['parent'] ?? null) === $key)));

        return count($keys) >= 2 ? ['keys' => array_slice($keys, 0, 4), 'alternative' => $clauses['alternative']] : [];
    }

    private function mentions(array $words, array $phrases): bool
    {
        $text = ' '.implode(' ', $words).' ';
        foreach ($phrases as $phrase) {
            if (str_contains($text, ' '.$phrase.' ')) {
                return true;
            }
        }

        return false;
    }

    /** A website-like topic: a project, or a service whose names say web, shop or store. */
    private function isWeb(?string $key, array $index): bool
    {
        $entity = $index['entities'][$key ?? ''] ?? null;
        if (! $entity) {
            return false;
        }

        return $entity['type'] === 'project' || array_intersect(array_merge($entity['tokens'], ...($entity['names'] ?? [])), (array) config('assistant.web_words', [])) !== [];
    }

    /** "A mobile app", "an iOS app": a separate application, not the website on phones. */
    private function mentionsApp(array $query): bool
    {
        return array_intersect($query['words'], (array) config('assistant.app_words', [])) !== [];
    }

    /** Usual words around a service name ("build", "need") not already explained. */
    private function genericCount(array $query, array $index, array $covered): int
    {
        $generic = $index['entity_words']['service'] ?? [];

        return count(array_filter($query['tokens'], fn ($token, $position) => isset($generic[$token]) && ! in_array($position, $covered, true), ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Services from different areas that fit the question about equally well ("integrations"): the
     * visitor chooses. Subservices of one service are not a choice; the best one is answered.
     *
     * @return list<string>
     */
    private function closeServices(array $strong, array $subjects): array
    {
        $top = $strong[0];
        if ($top['type'] === 'project') {
            return [];
        }
        $choices = [$top['family'] => $top['key']];
        foreach ($subjects as $subject) {
            if ($subject['type'] !== 'project' && ! isset($choices[$subject['family']]) && $subject['explained'] === $top['explained'] && $top['explained'] > 0
                && $top['score'] - $subject['score'] < 0.2 && $subject['positions'] !== [] && ! $subject['fuzzy']) {
                $choices[$subject['family']] = $subject['key'];
            }
        }

        return count($choices) > 1 ? array_slice(array_values($choices), 0, 3) : [];
    }

    /** @return list<string> folded names of the services and projects a question names */
    private function names(array $subjects, array $index): array
    {
        return array_map(fn ($s) => $this->normalizer->fold($index['entities'][$s['key']]['name']), array_filter($subjects, fn ($s) => $s['strong']));
    }

    /**
     * "Yes", "tell me more", "po": in order, the details of the service or project being discussed,
     * the rest of the list just shown, or the previous answer's first follow-up question.
     */
    private function continuation(array $index, array $context): ?array
    {
        if ($context['entity']) {
            return ['matched', $context['entity'], 'information'];
        }
        $previous = $index['intents'][$context['intent_id'] ?? 0] ?? null;
        if (! $previous) {
            return null;
        }
        if (in_array($previous['source_type'], ['services', 'projects'], true)) {
            return ['matched', $previous['id'], 'expand'];
        }
        $next = $this->responses->suggestions($index, $previous['followups'], [$previous['id']], 1);

        return $next ? ['matched', $next[0]['id'], 'continuation'] : null;
    }

    /**
     * What a projects answer is narrowed by: a topic in the question, else the service under
     * discussion ("do you have an example?" after e-commerce), named by its CMS name.
     */
    private function projectsTopic(array $query, array $index, ?string $entityKey, array $intent): array
    {
        $topic = $this->matcher->projectTopic($query, $index, null, $intent['vocab']);
        $subject = $entityKey ? ($index['entities'][$entityKey] ?? null) : null;
        if ($topic['terms'] !== [] || ! $subject || $subject['type'] === 'project') {
            return $topic;
        }
        // Projects the CMS relates to this service or its subservices.
        $related = array_keys(array_filter($index['entities'], fn ($e) => $e['type'] === 'project'
            && array_filter($e['services'] ?? [], fn ($key) => $key === $entityKey || ($index['entities'][$key]['family'] ?? null) === $entityKey || ($subject['family'] ?? null) === $key)));
        if ($related !== []) {
            return ['terms' => $subject['tokens'], 'keys' => $related, 'label' => $subject['name']];
        }
        // The words that name the service ("ecommerce"), not the generic ones ("solutions").
        $generic = $index['entity_words']['service'] ?? [];
        $distinct = array_values(array_filter($subject['tokens'], fn ($token) => ! isset($generic[$token])));
        if ($distinct === []) {
            return $topic;
        }
        $narrowed = $this->matcher->projectTopic($query, $index, $distinct, $intent['vocab']);

        return $narrowed['keys'] !== [] ? ['label' => $subject['name']] + $narrowed : ['terms' => [], 'keys' => [], 'label' => ''];
    }

    private function entityForCard(array $index, array $card): ?string
    {
        foreach ($index['entities'] as $key => $entity) {
            if ($entity['type'] === 'project' && ($entity['card']['href'] ?? null) === ($card['href'] ?? false)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * "Projects for online selling": asking for projects outright (project, example, portfolio)
     * favours the projects answer, narrowed by the topic, over a question about the topic itself.
     */
    private function preferProjects(array $decision, array $query, array $index, array $ranked): array
    {
        if ($decision[0] !== 'matched' || $decision[2] === 'exact' || $decision[2] === 'entity'
            || ($index['intents'][$decision[1]]['source_type'] ?? null) === 'projects'
            || array_intersect($query['tokens'], array_keys($index['entity_words']['project'] ?? [])) === []) {
            return $decision;
        }
        $config = config('assistant.matching');
        foreach ($ranked as $candidate) {
            if (($index['intents'][$candidate['id']]['source_type'] ?? null) === 'projects'
                && $candidate['score'] >= $config['candidate'] && $candidate['signals']['coverage'] >= $config['min_coverage']) {
                return ['matched', $candidate['id'], 'confident'];
            }
        }

        return $decision;
    }

    /**
     * A short follow-up that only names a topic ("what about ecommerce?", "and Laravel?") right
     * after a projects answer narrows that answer, unless something else was clearly asked.
     */
    private function narrowPreviousList(array $decision, array $query, array $index, array $context): array
    {
        $previous = $index['intents'][$context['intent_id'] ?? 0] ?? null;
        if (! $previous || $previous['source_type'] !== 'projects' || ($decision[0] === 'matched' && $decision[2] === 'exact')) {
            return $decision;
        }
        $topic = $this->matcher->projectTopic($query, $index, null, $previous['vocab']);
        if ($topic['keys'] === [] || count($topic['terms']) > 3) {
            return $decision;
        }
        // Another answer is kept unless the whole question is the topic ("what about ecommerce?").
        if ($decision[0] === 'matched' && $decision[2] !== 'entity' && array_diff($query['tokens'], $topic['terms']) !== []) {
            return $decision;
        }

        return ['matched', $previous['id'], 'context_topic'];
    }

    /** @return array{0: string, 1: mixed, 2: string} status, target, reason */
    private function decide(array $ranked, ?array $entity): array
    {
        $config = config('assistant.matching');
        $top = $ranked[0] ?? null;
        $second = $ranked[1] ?? null;

        if ($top && ($top['exact'] || ($top['score'] >= $config['confident'] && (! $second || $config['margin'] <= $top['score'] - $second['score'])))) {
            return ['matched', $top['id'], $top['exact'] ? 'exact' : 'confident'];
        }
        if ($entity) {
            return ['matched', $entity['key'], 'entity'];
        }
        // Offer plausible questions close to the best one; a choice must account for most of what was asked.
        $close = array_filter($ranked, fn ($r) => $top && $r['score'] >= $config['candidate'] && $top['score'] - $r['score'] < 0.2
            && $r['signals']['coverage'] >= $config['min_coverage']);
        if ($close) {
            return ['ambiguous', array_slice(array_column($close, 'id'), 0, (int) $config['max_candidates']), $top['score'] >= $config['confident'] ? 'close_scores' : 'low_confidence'];
        }

        return ['fallback', null, $top ? 'below_threshold' : 'no_match'];
    }

    /** Context from the client, kept only where it refers to content that still exists. */
    private function context(array $context, array $index): array
    {
        $intentId = isset($context['intent_id']) && is_int($context['intent_id']) && isset($index['intents'][$context['intent_id']]) ? $context['intent_id'] : null;
        $entity = isset($context['entity']) && is_string($context['entity']) && preg_match(self::ENTITY_PATTERN, $context['entity'])
            && isset($index['entities'][$context['entity']]) ? $context['entity'] : null;
        $turn = isset($context['turn']) && is_int($context['turn']) ? max(0, min(50, $context['turn'])) : 0;
        $act = isset($context['act']) && in_array($context['act'], self::ACTS, true) ? $context['act'] : null;
        $language = isset($context['language']) && is_string($context['language']) && preg_match(self::LANGUAGE_PATTERN, $context['language']) ? $context['language'] : null;
        // Lists of services, kept only where each still exists.
        $list = fn (string $field) => array_values(array_slice(array_filter((array) ($context[$field] ?? []), fn ($key) => is_string($key)
            && preg_match(self::ENTITY_PATTERN, $key) && isset($index['entities'][$key])), 0, 5));

        return ['intent_id' => $intentId, 'entity' => $entity, 'entities' => $list('entities'), 'excluded' => $list('excluded'), 'turn' => $turn, 'act' => $act, 'language' => $language];
    }

    private function debug(array $query, array $index, array $ranked, ?array $entity, string $reason): array
    {
        $concepts = [];
        foreach ($index['synonyms'] as $group) {
            if (isset($query['set'][$group['concept']])) {
                $concepts[] = array_map(fn ($term) => implode(' ', $term), $group['terms']);
            }
        }

        return [
            'normalized' => $query['text'],
            'tokens' => $query['tokens'],
            'synonyms' => $concepts,
            'entity' => $entity ? ['key' => $entity['key'], 'name' => $index['entities'][$entity['key']]['name'], 'score' => $entity['score']] : null,
            'reason' => $reason,
            'thresholds' => array_intersect_key(config('assistant.matching'), array_flip(['confident', 'candidate', 'margin'])),
            'candidates' => array_map(fn ($r) => [
                'id' => $r['id'], 'question' => $index['intents'][$r['id']]['question'], 'score' => $r['score'],
                'exact' => $r['exact'], 'signals' => $r['signals'], 'keywords' => $r['keywords'],
            ], array_slice($ranked, 0, 5)),
        ];
    }
}
