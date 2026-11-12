<?php

namespace App\Services\Meetings;

use App\Enums\MeetingFormat;
use App\Enums\MeetingLocationMode;
use App\Enums\VideoProvider;
use App\Models\MeetingBooking;
use App\Models\MeetingCalendarConnection;
use App\Models\MeetingType;
use App\Models\OfficeLocation;
use App\Services\Widgets\Types\MeetingBookingWidgetTypeService;
use Illuminate\Support\Facades\DB;

class MeetingTypeService
{
    public function __construct(private MeetingSettings $settings) {}

    /** The office an in-person meeting of this type takes place at, or null when none is usable. */
    public function officeFor(MeetingType $type): ?OfficeLocation
    {
        return $this->resolveOffice($type->office_location_id, (int) $type->channel_id);
    }

    public function defaultOffice(int $channelId): ?OfficeLocation
    {
        return OfficeLocation::query()->forChannel($channelId)->where('is_default', true)->where('active', true)->first();
    }

    /**
     * Whether online meetings can get a real link: Teams auto-creation
     * with a connected calendar, a static URL for the provider, or the admin
     * adding one after booking.
     *
     * @param  array{provider: string, auto_create: bool, static_urls: array, allow_manual_after_booking: bool}  $online
     */
    public function onlineLinkAvailable(?VideoProvider $override, array $online, int $channelId): bool
    {
        $provider = $override ?? VideoProvider::tryFrom($online['provider']) ?? VideoProvider::Teams;

        if ($provider->supportsAutoCreate() && $online['auto_create'] && $this->calendarConnected($channelId)) {
            return true;
        }

        return trim((string) ($online['static_urls'][$provider->value] ?? '')) !== '' || $online['allow_manual_after_booking'];
    }

    /**
     * Business-rule errors for a meeting type about to be saved, keyed by field.
     * The data is the validated form input.
     *
     * @return array<string, string>
     */
    public function validationErrors(array $data, int $channelId, ?array $online = null): array
    {
        $mode = MeetingLocationMode::from($data['location_mode']);
        $active = (bool) ($data['active'] ?? false);
        $errors = [];

        if (! $mode->allows(MeetingFormat::from($data['default_format']))) {
            $errors['default_format'] = __('meetings.error_default_format');
        }

        // Inactive types can be saved as drafts; they just can't be offered yet.
        if (! $active) {
            return $errors;
        }

        if ($mode->allows(MeetingFormat::InPerson) && ! $this->resolveOffice($data['office_location_id'] ?? null, $channelId)) {
            $errors['office_location_id'] = __('meetings.error_office_required');
        }

        $override = VideoProvider::tryFrom((string) ($data['video_provider'] ?? ''));
        if ($mode->allows(MeetingFormat::Online) && ! $this->onlineLinkAvailable($override, $online ?? $this->settings->online($channelId), $channelId)) {
            $errors['video_provider'] = __('meetings.error_online_link');
        }

        return $errors;
    }

    /** Active types whose online format would lose its link path under the given online settings. */
    public function typesWithoutOnlineLink(array $online, int $channelId)
    {
        return MeetingType::query()->forChannel($channelId)->where('active', true)
            ->whereIn('location_mode', [MeetingLocationMode::Online->value, MeetingLocationMode::Both->value])
            ->get()
            ->reject(fn (MeetingType $type) => $this->onlineLinkAvailable($type->video_provider, $online, $channelId))
            ->values();
    }

    public function save(?MeetingType $type, array $data, int $channelId): MeetingType
    {
        return DB::transaction(function () use ($type, $data, $channelId) {
            $type ??= new MeetingType(['channel_id' => $channelId]);
            $type->fill(collect($data)->except('translations')->all())->save();
            $this->saveTranslations($type, (array) ($data['translations'] ?? []));
            MeetingBookingWidgetTypeService::refreshWidgets();

            return $type;
        });
    }

    /** Upcoming bookings that stop a type from being deactivated or deleted. */
    public function upcomingBookings(MeetingType $type): int
    {
        return MeetingBooking::query()->where('meeting_type_id', $type->id)->upcomingBlocking()->count();
    }

    private function resolveOffice(mixed $officeId, int $channelId): ?OfficeLocation
    {
        if (! $officeId) {
            $office = $this->defaultOffice($channelId);
        } else {
            $office = OfficeLocation::query()->forChannel($channelId)->where('active', true)->find($officeId);
        }

        return $office?->hasCompleteAddress() ? $office : null;
    }

    private function calendarConnected(int $channelId): bool
    {
        return (bool) MeetingCalendarConnection::query()->where('channel_id', $channelId)->first()?->isConnected();
    }

    private function saveTranslations(MeetingType $type, array $translations): void
    {
        foreach ($this->settings->translationLocales() as $locale) {
            $content = collect(MeetingType::TEXT_FIELDS)
                ->mapWithKeys(fn ($field) => [$field => trim((string) ($translations[$locale][$field] ?? ''))])
                ->filter()
                ->all();

            if ($content) {
                $type->translations()->updateOrCreate(['locale' => $locale], ['content' => $content]);
            } else {
                $type->translations()->where('locale', $locale)->delete();
            }
        }
    }
}
