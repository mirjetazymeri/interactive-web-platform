<?php

namespace App\Http\Controllers\Admin\Meetings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meetings\OfficeLocationRequest;
use App\Models\Country;
use App\Models\OfficeLocation;
use App\Services\Meetings\MeetingSettings;
use App\Services\Meetings\OfficeLocationService;
use App\Services\Widgets\Types\MeetingBookingWidgetTypeService;

class OfficeLocationController extends Controller
{
    public function __construct(private OfficeLocationService $offices, private MeetingSettings $settings) {}

    public function index()
    {
        $locations = OfficeLocation::query()->forChannel($this->channelId())
            ->with('country')
            ->orderBy('position')->orderBy('name')
            ->get();

        return view('admin.meetings.locations.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.meetings.locations.create', $this->formData());
    }

    public function store(OfficeLocationRequest $request)
    {
        $this->offices->save(null, $request->payload(), $this->channelId());

        return redirect()->route('admin.meetings.locations.index')->with('success', __('meetings.location_saved'));
    }

    public function edit(int $location)
    {
        return view('admin.meetings.locations.edit', $this->formData($this->find($location)));
    }

    public function update(OfficeLocationRequest $request, int $location)
    {
        $office = $this->find($location);
        $data = $request->payload();

        $removing = $office->active && ! $data['active'];
        $losesDefault = $office->is_default && ! $data['is_default'];
        if ($removing || $losesDefault) {
            $blockers = $this->offices->blockers($office, $removing);
            if ($blockers) {
                return back()->withInput()->withErrors(['active' => $blockers]);
            }
        }

        $this->offices->save($office, $data, $this->channelId());

        return redirect()->route('admin.meetings.locations.edit', $office->id)->with('success', __('meetings.location_saved'));
    }

    public function destroy(int $location)
    {
        $office = $this->find($location);

        if ($blockers = $this->offices->blockers($office, true)) {
            return back()->withErrors(['location' => $blockers]);
        }

        $office->delete();
        MeetingBookingWidgetTypeService::refreshWidgets();

        return redirect()->route('admin.meetings.locations.index')->with('success', __('meetings.location_deleted'));
    }

    private function formData(?OfficeLocation $location = null): array
    {
        return [
            'location' => $location?->load('translations'),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'translationLocales' => $this->settings->translationLocales(),
        ];
    }

    private function find(int $id): OfficeLocation
    {
        return OfficeLocation::query()->forChannel($this->channelId())->findOrFail($id);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
