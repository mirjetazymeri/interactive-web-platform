<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MeetingFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MeetingAvailabilityRequest;
use App\Models\MeetingType;
use App\Services\Meetings\AvailabilityService;
use App\Services\Meetings\Calendar\CalendarUnavailableException;
use App\Services\Meetings\MeetingSettings;

class MeetingAvailabilityController extends Controller
{
    // GET /api/v1/meetings/availability?type=slug&format=online&from=...&to=...
    public function index(MeetingAvailabilityRequest $request, AvailabilityService $availability, MeetingSettings $settings)
    {
        $channelId = (int) $request->attributes->get('channel_id');
        $type = MeetingType::query()->forChannel($channelId)->where('active', true)->where('slug', $request->input('type'))->first();
        $format = MeetingFormat::from($request->input('format'));

        if (! $type || ! $type->allows($format)) {
            return response()->json(['message' => __('meetings.booking_error_format_unavailable'), 'code' => 'format_unavailable'], 422);
        }

        try {
            $slots = $availability->slots($type, $format, $request->from(), $request->to());
        } catch (CalendarUnavailableException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => $e->reason], 503);
        }

        return response()->json([
            'timezone' => $settings->timezone($channelId),
            'slots' => array_map(fn (array $slot) => [
                'start' => $slot['start']->toIso8601ZuluString(),
                'end' => $slot['end']->toIso8601ZuluString(),
            ], $slots),
        ]);
    }
}
