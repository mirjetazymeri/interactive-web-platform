<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsletterSubscriber\StoreNewsletterSubscriberRequest;
use App\Http\Requests\Admin\NewsletterSubscriber\UpdateNewsletterSubscriberRequest;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterSubscriberService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $status = (string) $request->input('status');
        $channelId = $this->channelId();

        $subscribers = NewsletterSubscriber::query()
            ->forChannel($channelId)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('email', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.newsletters.index', compact('subscribers', 'q', 'status'));
    }

    public function create()
    {
        return view('admin.newsletters.create');
    }

    public function store(StoreNewsletterSubscriberRequest $request, NewsletterSubscriberService $service)
    {
        $data = $request->validated();

        $subscriber = $service->subscribe(
            $data['email'],
            $data['name'] ?: null,
            $this->channelId(),
            'admin'
        );

        if ($data['status'] === 'unsubscribed') {
            $subscriber->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.newsletters.index')
            ->with('success', 'Newsletter subscriber created.');
    }

    public function edit(int $id)
    {
        $subscriber = $this->subscriberQuery()->findOrFail($id);

        return view('admin.newsletters.edit', compact('subscriber'));
    }

    public function update(UpdateNewsletterSubscriberRequest $request, int $id)
    {
        $subscriber = $this->subscriberQuery()->findOrFail($id);
        $data = $request->validated();

        $subscriber->fill([
            'name' => $data['name'] ?: null,
            'email' => Str::lower(trim($data['email'])),
            'status' => $data['status'],
            'source' => $subscriber->source ?: 'admin',
        ]);

        if (!$subscriber->unsubscribe_token) {
            $subscriber->unsubscribe_token = (string) Str::uuid();
        }

        if ($data['status'] === 'subscribed') {
            $subscriber->subscribed_at = $subscriber->subscribed_at ?: now();
            $subscriber->unsubscribed_at = null;
        } else {
            $subscriber->unsubscribed_at = $subscriber->unsubscribed_at ?: now();
        }

        $subscriber->save();

        return back()->with('success', 'Newsletter subscriber updated.');
    }

    public function delete(int $id)
    {
        $this->subscriberQuery()->findOrFail($id)->delete();

        return redirect()
            ->route('admin.newsletters.index')
            ->with('success', 'Newsletter subscriber deleted.');
    }

    protected function subscriberQuery()
    {
        return NewsletterSubscriber::query()->forChannel($this->channelId());
    }

    protected function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
