<?php

namespace App\Services\Assistant;

use App\Models\AssistantCategory;
use App\Models\AssistantCategoryTranslation;
use App\Models\AssistantIntent;
use App\Models\AssistantIntentTranslation;
use App\Models\AssistantKnowledgeProfile;
use App\Models\AssistantKnowledgeProfileTranslation;
use App\Models\AssistantProfile;
use App\Models\AssistantProfileTranslation;
use App\Models\AssistantSynonym;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\PageWidget;
use App\Models\Setting;
use App\Models\Widget;
use App\Models\WidgetTranslation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cached matching indexes, one per channel and language. The cache key
 * carries a fingerprint of everything an index is built from (assistant
 * content, widgets, pages, placements, settings), so any change, from the
 * admin or elsewhere, is picked up on the next question without explicit
 * invalidation hooks. Works with any cache store; Redis is not required.
 */
class AssistantIndexRepository
{
    /** Tables an index depends on: [table, scoped to the channel, has updated_at]. */
    private const SOURCES = [
        ['assistant_profiles', true, true],
        ['assistant_profile_translations', false, true],
        ['assistant_categories', true, true],
        ['assistant_category_translations', false, true],
        ['assistant_intents', true, true],
        ['assistant_intent_translations', false, true],
        ['assistant_intent_followups', false, false],
        ['assistant_synonyms', true, true],
        ['assistant_knowledge_profiles', true, true],
        ['assistant_knowledge_profile_translations', false, true],
        ['widgets', false, true],
        ['widget_translations', false, true],
        ['widget_channel', false, false],
        ['pages', false, true],
        ['page_translations', false, true],
        ['page_channel', false, false],
        ['page_widgets', false, true],
        ['settings', true, true],
    ];

    /** Changed on every save of a model an index depends on; catches edits within the same second. */
    public const VERSION_KEY = 'assistant:index:version';

    /** The compiled index layout; raise it when the compiler changes so deployed code never reads an old index. */
    public const FORMAT = 7;

    /** Models whose saves change what the assistant answers. */
    private const WATCHED = [
        AssistantProfile::class, AssistantProfileTranslation::class,
        AssistantCategory::class, AssistantCategoryTranslation::class,
        AssistantIntent::class, AssistantIntentTranslation::class,
        AssistantSynonym::class, AssistantKnowledgeProfile::class, AssistantKnowledgeProfileTranslation::class,
        Widget::class, WidgetTranslation::class,
        Page::class, PageTranslation::class, PageWidget::class,
        Setting::class,
    ];

    public function __construct(private AssistantIndexCompiler $compiler) {}

    public static function watchModels(): void
    {
        foreach (self::WATCHED as $model) {
            $model::saved(fn () => self::bump());
            $model::deleted(fn () => self::bump());
        }
    }

    public static function bump(): void
    {
        try {
            Cache::forever(self::VERSION_KEY, bin2hex(random_bytes(8)));
        } catch (\Throwable $e) {
            // Without the cache the timestamp fingerprint still applies.
            report($e);
        }
    }

    public function get(int $channelId, string $locale): array
    {
        $key = 'assistant:index:v'.self::FORMAT.':'.$channelId.':'.$locale.':'.$this->fingerprint($channelId);

        return Cache::remember($key, now()->addMinutes((int) config('assistant.cache_ttl_minutes', 1440)),
            fn () => $this->compiler->compile($channelId, $locale));
    }

    /** Languages an index can be built for. */
    public function locales(): array
    {
        return $this->compiler->locales();
    }

    public function fingerprint(int $channelId): string
    {
        $parts = [(string) Cache::get(self::VERSION_KEY, '')];
        foreach (self::SOURCES as [$table, $scoped, $timestamps]) {
            $query = DB::table($table)->when($scoped, fn ($q) => $q->where('channel_id', $channelId));
            $row = $query->selectRaw($timestamps ? 'count(*) as total, max(updated_at) as latest' : 'count(*) as total, null as latest')->first();
            $parts[] = $table.':'.$row->total.':'.$row->latest;
        }
        // Pages and placements can go live or expire by schedule without any row changing.
        $parts[] = Page::query()->published()->count().':'.PageWidget::query()->active()->count();

        return substr(hash('sha256', implode('|', $parts)), 0, 20);
    }
}
