<?php

namespace App\Services\Meetings;

use App\Enums\MeetingLocationMode;
use App\Models\MeetingBooking;
use App\Models\MeetingType;
use App\Models\OfficeLocation;
use App\Services\Widgets\Types\MeetingBookingWidgetTypeService;
use Illuminate\Support\Facades\DB;

class OfficeLocationService
{
    public function __construct(private MeetingSettings $settings) {}

    public function save(?OfficeLocation $office, array $data, int $channelId): OfficeLocation
    {
        return DB::transaction(function () use ($office, $data, $channelId) {
            $office ??= new OfficeLocation(['channel_id' => $channelId]);
            $office->fill(collect($data)->except('translations')->all())->save();

            foreach ($this->settings->translationLocales() as $locale) {
                $content = collect(OfficeLocation::TEXT_FIELDS)
                    ->mapWithKeys(fn ($field) => [$field => trim((string) ($data['translations'][$locale][$field] ?? ''))])
                    ->filter()
                    ->all();

                if ($content) {
                    $office->translations()->updateOrCreate(['locale' => $locale], ['content' => $content]);
                } else {
                    $office->translations()->where('locale', $locale)->delete();
                }
            }

            MeetingBookingWidgetTypeService::refreshWidgets();

            return $office;
        });
    }

    /**
     * Reasons the office cannot be taken out of use. Removing it (deactivating
     * or deleting) affects types that use it directly and upcoming bookings
     * there; as the default office, removing it or unsetting the default also
     * affects types that rely on the default.
     *
     * @return list<string>
     */
    public function blockers(OfficeLocation $office, bool $removing): array
    {
        if (! $removing && ! $office->is_default) {
            return [];
        }

        $reasons = [];

        $types = MeetingType::query()->forChannel($office->channel_id)->where('active', true)
            ->whereIn('location_mode', [MeetingLocationMode::InPerson->value, MeetingLocationMode::Both->value])
            ->where(function ($query) use ($office, $removing) {
                $query->when($removing, fn ($q) => $q->orWhere('office_location_id', $office->id))
                    ->when($office->is_default, fn ($q) => $q->orWhereNull('office_location_id'));
            })
            ->pluck('name');

        if ($types->isNotEmpty()) {
            $reasons[] = __('meetings.error_office_in_use', ['types' => $types->implode(', ')]);
        }

        $bookings = $removing ? MeetingBooking::query()->where('office_location_id', $office->id)->upcomingBlocking()->count() : 0;
        if ($bookings) {
            $reasons[] = trans_choice('meetings.error_office_bookings', $bookings, ['count' => $bookings]);
        }

        return $reasons;
    }
}
