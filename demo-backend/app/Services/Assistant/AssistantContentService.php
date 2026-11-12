<?php

namespace App\Services\Assistant;

use App\Models\AssistantCategory;
use App\Models\AssistantIntent;
use App\Models\AssistantKnowledgeProfile;
use App\Models\AssistantProfile;
use App\Models\AssistantSynonym;
use App\Models\AssistantUnansweredQuestion;
use App\Models\Locale;
use App\Services\Assistant\Resolvers\PagesResolver;
use App\Services\Assistant\Resolvers\ProjectsResolver;
use App\Services\Assistant\Resolvers\ServicesResolver;
use Illuminate\Support\Facades\DB;

/** Admin-side writes and form options for the assistant. Base fields are the default language. */
class AssistantContentService
{
    public function __construct(
        private PagesResolver $pages,
        private ServicesResolver $services,
        private ProjectsResolver $projects,
    ) {}

    public function defaultLocale(): string
    {
        return (string) config('app.locale');
    }

    /** Languages with translation tabs: active CMS locales and configured ones, without the default. */
    public function translationLocales(): array
    {
        return collect(Locale::activeCodes())
            ->merge(array_keys(config('locales', [])))
            ->unique()
            ->reject(fn ($code) => $code === $this->defaultLocale())
            ->values()
            ->all();
    }

    public function profile(int $channelId): AssistantProfile
    {
        return AssistantProfile::query()->forChannel($channelId)->with('translations')->first()
            ?? new AssistantProfile(['channel_id' => $channelId, 'enabled' => false, 'show_on_mobile' => true, 'log_unanswered' => true, 'unanswered_retention_days' => 90]);
    }

    public function saveProfile(int $channelId, array $data): AssistantProfile
    {
        return DB::transaction(function () use ($channelId, $data) {
            $profile = AssistantProfile::query()->firstOrNew(['channel_id' => $channelId]);
            $profile->fill(collect($data)->except('translations')->all())->save();
            $this->saveTranslations($profile, AssistantProfile::TEXT_FIELDS, [], (array) ($data['translations'] ?? []));

            return $profile;
        });
    }

    public function saveIntent(?AssistantIntent $intent, array $data, int $channelId): AssistantIntent
    {
        return DB::transaction(function () use ($intent, $data, $channelId) {
            $intent ??= new AssistantIntent(['channel_id' => $channelId]);
            if (! $intent->exists && ! isset($data['position'])) {
                $data['position'] = (int) AssistantIntent::query()->forChannel($channelId)->max('position') + 1;
            }
            if (($data['is_suggested'] ?? false) && ! $intent->is_suggested) {
                $data['suggested_position'] = (int) AssistantIntent::query()->forChannel($channelId)->max('suggested_position') + 1;
            }
            $intent->fill(collect($data)->except(['translations', 'followups', 'from_unanswered'])->all())->save();
            $this->saveTranslations($intent, AssistantIntent::TEXT_FIELDS, AssistantIntent::LIST_FIELDS, (array) ($data['translations'] ?? []));

            $followups = collect($data['followups'] ?? [])->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === $intent->id)->unique()->values();
            $intent->followups()->sync($followups->mapWithKeys(fn ($id, $position) => [$id => ['position' => $position]])->all());
            // Pivot changes do not touch the intent; the index fingerprint needs a fresh timestamp.
            $intent->touch();

            if (! empty($data['from_unanswered'])) {
                AssistantUnansweredQuestion::query()->forChannel($channelId)->whereKey($data['from_unanswered'])
                    ->update(['status' => 'resolved', 'resolved_intent_id' => $intent->id]);
            }

            return $intent;
        });
    }

    /** The channel's Company Knowledge Profile with its translations; base text is the default language. */
    public function saveKnowledge(int $channelId, array $data): AssistantKnowledgeProfile
    {
        return DB::transaction(function () use ($channelId, $data) {
            $profile = AssistantKnowledgeProfile::query()->firstOrNew(['channel_id' => $channelId]);
            $profile->fill(collect($data)->except('translations')->all())->save();
            $this->saveTranslations($profile, AssistantKnowledgeProfile::TEXT_FIELDS, ['capabilities'], (array) ($data['translations'] ?? []));
            $profile->touch();

            return $profile;
        });
    }

    public function saveCategory(?AssistantCategory $category, array $data, int $channelId): AssistantCategory
    {
        return DB::transaction(function () use ($category, $data, $channelId) {
            $category ??= new AssistantCategory(['channel_id' => $channelId]);
            $category->fill(collect($data)->except('translations')->all())->save();
            $this->saveTranslations($category, AssistantCategory::TEXT_FIELDS, [], (array) ($data['translations'] ?? []));

            return $category;
        });
    }

    public function saveSynonym(?AssistantSynonym $synonym, array $data, int $channelId): AssistantSynonym
    {
        $synonym ??= new AssistantSynonym(['channel_id' => $channelId]);
        $synonym->fill($data)->save();

        return $synonym;
    }

    /** Adds an unanswered question as another phrasing of an existing question, in the question's language. */
    public function addPhrasing(AssistantIntent $intent, AssistantUnansweredQuestion $question): void
    {
        DB::transaction(function () use ($intent, $question) {
            $text = $question->question;
            if ($question->locale === $this->defaultLocale()) {
                $intent->phrasings = array_values(array_unique([...($intent->phrasings ?? []), $text]));
                $intent->save();
            } else {
                $translation = $intent->translations()->firstOrNew(['locale' => $question->locale]);
                $content = (array) ($translation->content ?? []);
                $content['phrasings'] = array_values(array_unique([...($content['phrasings'] ?? []), $text]));
                $translation->content = $content;
                $translation->save();
                $intent->touch();
            }
            $question->update(['status' => 'resolved', 'resolved_intent_id' => $intent->id]);
        });
    }

    /**
     * CMS content an answer can show, as select options grouped for the form.
     *
     * @return array{general: array<string, string>, services: array<string, string>, projects: array<string, string>}
     */
    public function sourceOptions(int $channelId): array
    {
        $locale = $this->defaultLocale();
        $services = [];
        foreach ($this->services->all($channelId, $locale) as $service) {
            $services[$service['key']] = $service['parent'] ? $service['parent'].' › '.$service['name'] : $service['name'];
        }
        $projects = [];
        foreach ($this->projects->all($channelId, $locale) as $project) {
            $projects[$project['key']] = $project['name'];
        }

        return [
            'general' => ['contact' => __('assistant.source_contact'), 'services' => __('assistant.source_services'), 'projects' => __('assistant.source_projects'), 'company' => __('assistant.source_company')],
            'services' => $services,
            'projects' => $projects,
        ];
    }

    /**
     * Published services and subservices a synonym group can name, by their CMS reference
     * ("svc-id", "svc-id/sub-id"), which stays the same whichever widget shows them.
     *
     * @return array<string, string>
     */
    public function serviceRefOptions(int $channelId): array
    {
        $options = [];
        foreach ($this->services->all($channelId, $this->defaultLocale()) as $service) {
            $options[$service['ref']] = $service['parent'] ? $service['parent'].' › '.$service['name'] : $service['name'];
        }

        return $options;
    }

    /** "subservice:12:development/ecommerce" → ['subservice', '12:development/ecommerce']; "contact" → ['contact', null]. */
    public static function splitSource(?string $source): array
    {
        if (! $source) {
            return [null, null];
        }
        [$type, $id] = array_pad(explode(':', $source, 2), 2, null);

        return [$type, $id];
    }

    public static function joinSource(?string $type, ?string $id): ?string
    {
        return $type ? ($id ? $type.':'.$id : $type) : null;
    }

    /** @return array<int, string> reachable pages by id, labelled with their path */
    public function pageOptions(int $channelId): array
    {
        $options = [];
        foreach ($this->pages->reachable($channelId) as $id => $entry) {
            $options[$id] = $entry['page']->title.' — /'.($entry['path'] === 'home' ? '' : $entry['path']);
        }
        asort($options);

        return $options;
    }

    private function saveTranslations($model, array $textFields, array $listFields, array $translations): void
    {
        foreach ($this->translationLocales() as $locale) {
            $content = [];
            foreach ($textFields as $field) {
                $value = trim((string) ($translations[$locale][$field] ?? ''));
                if ($value !== '') {
                    $content[$field] = $value;
                }
            }
            foreach ($listFields as $field) {
                $list = array_values(array_filter((array) ($translations[$locale][$field] ?? [])));
                if ($list !== []) {
                    $content[$field] = $list;
                }
            }

            if ($content) {
                $model->translations()->updateOrCreate(['locale' => $locale], ['content' => $content]);
            } else {
                $model->translations()->where('locale', $locale)->delete();
            }
        }
    }
}
