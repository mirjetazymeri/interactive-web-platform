<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MeetingFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMeetingBookingRequest;
use App\Models\MeetingType;
use App\Services\Meetings\BookingException;
use App\Services\Meetings\BookingService;
use App\Services\Meetings\Calendar\CalendarUnavailableException;
use App\Services\Meetings\MeetingLocationPresenter;
use Carbon\CarbonImmutable;

class MeetingBookingController extends Controller
{
    // POST /api/v1/meetings/bookings
    public function store(StoreMeetingBookingRequest $request, BookingService $bookings, MeetingLocationPresenter $presenter)
    {
        $channelId = (int) $request->attributes->get('channel_id');
        $type = MeetingType::query()->forChannel($channelId)->where('active', true)->where('slug', $request->input('type'))->first();

        if (! $type) {
            return response()->json(['message' => __('meetings.booking_error_type_unavailable'), 'code' => BookingException::TYPE_UNAVAILABLE], 422);
        }

        try {
            $booking = $bookings->create(
                $type,
                MeetingFormat::from($request->input('format')),
                CarbonImmutable::parse($request->input('start')),
                $request->visitor(),
                $request->input('locale'),
                $request->input('visitor_timezone'),
            );
        } catch (BookingException $e) {
            $status = match ($e->reason) {
                BookingException::SLOT_TAKEN => 409,
                CalendarUnavailableException::CALENDAR_UNAVAILABLE => 503,
                default => 422,
            };

            return response()->json(['message' => $e->getMessage(), 'code' => $e->reason], $status);
        }

        return response()->json([
            'reference' => $booking->reference,
            'status' => $booking->status,
            'format' => $booking->format->value,
            'start' => $booking->starts_at->toIso8601ZuluString(),
            'end' => $booking->ends_at->toIso8601ZuluString(),
            'location' => $presenter->publicLocation($booking),
        ], 201);
    }
}
