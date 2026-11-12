<?php

namespace App\Services\Meetings;

use App\Enums\MeetingFormat;
use App\Enums\VideoProvider;
use App\Models\MeetingBooking;
use App\Models\OfficeLocation;

/**
 * The one place that decides what a visitor is told about where a meeting
 * happens. A video URL is only ever returned once it is real (status
 * "ready"); otherwise the "link will follow" wording is used.
 */
class MeetingLocationPresenter
{
    public function __construct(private MeetingSettings $settings) {}

    /** Office details as stored on a booking, in the booking's language. */
    public function snapshot(OfficeLocation $office, string $locale): array
    {
        $office->loadMissing('translations', 'country');

        return [
            'office_id' => $office->id,
            'name' => $office->translated('name', $locale),
            'address' => $office->fullAddress(),
            'maps_url' => $office->mapsLink(),
            'arrival_instructions' => $office->translated('arrival_instructions', $locale),
        ];
    }

    /**
     * Everything emails and the admin show about the meeting's location.
     *
     * @return array{kind: string, link_ready?: bool, url?: ?string, provider?: ?string, name?: ?string, address?: ?string, maps_url?: ?string, instructions: list<string>}
     */
    public function present(MeetingBooking $booking): array
    {
        $type = $booking->meetingType->loadMissing('translations');
        $locale = $booking->locale;

        if ($booking->format === MeetingFormat::Online) {
            $ready = $booking->video_link_status === 'ready' && filter_var($booking->video_url, FILTER_VALIDATE_URL);
            $joining = $this->settings->array('online.joining_instructions', (int) $booking->channel_id);

            return [
                'kind' => MeetingFormat::Online->value,
                'link_ready' => (bool) $ready,
                'url' => $ready ? $booking->video_url : null,
                'provider' => VideoProvider::tryFrom((string) $booking->video_provider)?->label(),
                'instructions' => array_values(array_filter([
                    $joining[$locale] ?? $joining[$this->settings->defaultLocale()] ?? null,
                    $type->translated('online_instructions', $locale),
                ])),
            ];
        }

        $office = (array) $booking->location_snapshot;

        return [
            'kind' => MeetingFormat::InPerson->value,
            'name' => $office['name'] ?? null,
            'address' => $office['address'] ?? null,
            'maps_url' => $office['maps_url'] ?? null,
            'instructions' => array_values(array_filter([
                $office['arrival_instructions'] ?? null,
                $type->translated('in_person_instructions', $locale),
            ])),
        ];
    }

    /** What the public booking response may reveal: no video URL, only whether it is ready. */
    public function publicLocation(MeetingBooking $booking): array
    {
        $location = $this->present($booking);

        if ($location['kind'] === MeetingFormat::Online->value) {
            return ['kind' => $location['kind'], 'link_status' => $location['link_ready'] ? 'ready' : 'pending'];
        }

        return $location;
    }
}
