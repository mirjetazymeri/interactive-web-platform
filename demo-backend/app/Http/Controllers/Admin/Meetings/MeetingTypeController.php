<?php

namespace App\Http\Controllers\Admin\Meetings;

use App\Enums\MeetingFormat;
use App\Enums\MeetingLocationMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meetings\MeetingTypeRequest;
use App\Models\MeetingType;
use App\Models\OfficeLocation;
use App\Services\Meetings\MeetingSettings;
use App\Services\Meetings\MeetingTypeService;
use App\Services\Widgets\Types\MeetingBookingWidgetTypeService;

class MeetingTypeController extends Controller
{
    public function __construct(private MeetingTypeService $types, private MeetingSettings $settings) {}

    public function index()
    {
        $types = MeetingType::query()->forChannel($this->channelId())
            ->with('officeLocation')
            ->orderBy('position')->orderBy('name')
            ->get();

        return view('admin.meetings.types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.meetings.types.create', $this->formData());
    }

    public function store(MeetingTypeRequest $request)
    {
        $type = $this->types->save(null, $request->payload(), $this->channelId());

        return redirect()->route('admin.meetings.types.edit', $type->id)->with('success', __('meetings.type_saved'));
    }

    public function edit(int $type)
    {
        return view('admin.meetings.types.edit', $this->formData($this->find($type)));
    }

    public function update(MeetingTypeRequest $request, int $type)
    {
        $meetingType = $this->find($type);
        $data = $request->payload();

        if ($meetingType->active && ! $data['active'] && ($count = $this->types->upcomingBookings($meetingType))) {
            return back()->withInput()->withErrors(['active' => trans_choice('meetings.error_type_bookings', $count, ['count' => $count])]);
        }

        $this->types->save($meetingType, $data, $this->channelId());

        return redirect()->route('admin.meetings.types.edit', $meetingType->id)->with('success', __('meetings.type_saved'));
    }

    public function destroy(int $type)
    {
        $meetingType = $this->find($type);

        if ($count = $this->types->upcomingBookings($meetingType)) {
            return back()->withErrors(['type' => trans_choice('meetings.error_type_bookings', $count, ['count' => $count])]);
        }

        $meetingType->delete();
        MeetingBookingWidgetTypeService::refreshWidgets();

        return redirect()->route('admin.meetings.types.index')->with('success', __('meetings.type_deleted'));
    }

    private function formData(?MeetingType $type = null): array
    {
        $channelId = $this->channelId();

        return [
            'type' => $type?->load('translations'),
            'modes' => MeetingLocationMode::cases(),
            'formats' => MeetingFormat::cases(),
            'offices' => OfficeLocation::query()->forChannel($channelId)->where('active', true)->orderBy('position')->orderBy('name')->get(),
            'defaultOffice' => $this->types->defaultOffice($channelId),
            'globalProvider' => $this->settings->provider($channelId),
            'defaults' => config('meetings.type_defaults'),
            'translationLocales' => $this->settings->translationLocales(),
        ];
    }

    private function find(int $id): MeetingType
    {
        return MeetingType::query()->forChannel($this->channelId())->findOrFail($id);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
