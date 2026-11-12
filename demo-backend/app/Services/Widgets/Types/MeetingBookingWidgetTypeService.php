<?php

namespace App\Services\Widgets\Types;

use App\Enums\MeetingFormat;
use App\Models\MeetingType;
use App\Models\Widget;
use App\Services\Meetings\MeetingSettings;
use App\Services\Meetings\MeetingTypeService;
use Illuminate\Http\Request;

/**
 * Meeting booking: lets visitors book one of the chosen meeting types. The
 * widget stores which types to offer and its visible copy; the meeting types,
 * formats, durations and timezone are read live from the Meetings module.
 */
class MeetingBookingWidgetTypeService implements WidgetTypeConfigService
{
    public const TYPE = 'meeting_booking';

    /** Translatable copy. Empty fields fall back to the defaults in lang/{locale}/meetings.php (widget.*). */
    public const TEXT_FIELDS = [
        'title', 'subtitle',
        'online_title', 'online_line', 'online_note',
        'in_person_title', 'in_person_line', 'in_person_note',
        'consent_text', 'submit_label', 'success_title', 'success_message',
    ];

    /** Longer fields; the rest are single lines. */
    public const LONG_FIELDS = ['subtitle', 'consent_text', 'success_message'];

    public function __construct(private MeetingTypeService $types, private MeetingSettings $settings) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['meeting_types'] = collect((array) ($incoming['meeting_types'] ?? []))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        foreach (self::TEXT_FIELDS as $field) {
            $config[$field] = trim((string) ($incoming[$field] ?? ''));
        }

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        return collect(self::TEXT_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => trim((string) ($translation[$field] ?? ''))])
            ->filter()
            ->all();
    }

    /**
     * The public config: copy with defaults filled in, and the chosen meeting
     * types that are active and bookable right now, each with the formats it
     * can actually offer (in person only when an office with an address exists).
     */
    public function representation(Widget $widget, array $config, string $locale): array
    {
        $channelId = (int) ((app()->bound('currentChannel') ? app('currentChannel')?->id : null) ?? $widget->channels->first()?->id);

        $types = MeetingType::query()->forChannel($channelId)->where('active', true)
            ->whereIn('id', (array) ($config['meeting_types'] ?? []))
            ->with('translations')
            ->orderBy('position')->orderBy('name')
            ->get()
            ->map(fn (MeetingType $type) => $this->publicType($type, $locale))
            ->filter(fn (array $type) => $type['formats'] !== [])
            ->values()
            ->all();

        $copy = collect(self::TEXT_FIELDS)->mapWithKeys(fn ($field) => [
            $field => trim((string) ($config[$field] ?? '')) ?: __('meetings.widget.'.$field, [], $locale),
        ])->all();

        return [
            'section_label' => (string) ($config['section_label'] ?? ''),
            'show_title' => (bool) ($config['show_title'] ?? false),
            ...$copy,
            'timezone' => $this->settings->timezone($channelId),
            'types' => $types,
        ];
    }

    /** Make pages showing booking widgets re-render (their cached JSON is keyed on the widget). */
    public static function refreshWidgets(): void
    {
        Widget::query()->where('type', self::TYPE)->update(['updated_at' => now()]);
    }

    private function publicType(MeetingType $type, string $locale): array
    {
        $formats = collect($type->allowedFormats())
            ->filter(fn (MeetingFormat $format) => $format !== MeetingFormat::InPerson || $this->types->officeFor($type))
            ->map(fn (MeetingFormat $format) => ['format' => $format->value, 'duration' => $type->settingsFor($format)['duration']])
            ->values()
            ->all();
        $offered = array_column($formats, 'format');

        return [
            'slug' => $type->slug,
            'name' => $type->translated('name', $locale),
            'description' => $type->translated('description', $locale),
            'default_format' => in_array($type->default_format->value, $offered, true) ? $type->default_format->value : ($offered[0] ?? null),
            'formats' => $formats,
        ];
    }
}
