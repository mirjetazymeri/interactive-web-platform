<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Channel;

class ChannelController extends Controller
{
    public function index()
    {
        $channels = Channel::all();
        return view('admin.channels.index', compact('channels'));
    }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:100|unique:channels,code',
                'domain' => 'nullable|string'
            ]);

            Channel::create($request->only('name', 'code', 'domain'));

            return redirect()->route('admin.channels.index')->with('success', 'Channel created.');
        }

        return view('admin.channels.create');
    }

    public function edit(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);

        if ($request->isMethod('post')) {

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:100|unique:channels,code,' . $channel->id,
                'domain' => 'nullable|string'
            ]);

            $channel->update($request->only('name', 'code', 'domain'));

            return redirect()->route('admin.channels.index')->with('success', 'Channel updated.');
        }

        return view('admin.channels.edit', compact('channel'));
    }

    public function delete($id)
    {
        $channel = Channel::findOrFail($id);

        if ($channel->code === 'default') {
            return back()->with('error', 'Cannot delete default channel.');
        }

        $channel->delete();

        return redirect()->route('admin.channels.index')->with('success', 'Channel deleted.');
    }


    public function switch($id)
    {
        $user = auth()->user();

        // 🔐 ensure user has access to this channel
        if (!$user->channels()->where('channels.id', $id)->exists()) {
            abort(403, 'Unauthorized channel');
        }

        $user->update([
            'current_channel_id' => $id
        ]);

        // optional but useful
        $user->refresh();

        return back()->with('success', 'Channel switched.');
    }
}
