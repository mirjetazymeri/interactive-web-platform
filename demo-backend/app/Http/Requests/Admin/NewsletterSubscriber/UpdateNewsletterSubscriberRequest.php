<?php

namespace App\Http\Requests\Admin\NewsletterSubscriber;

use Illuminate\Validation\Rule;

class UpdateNewsletterSubscriberRequest extends StoreNewsletterSubscriberRequest
{
    public function rules(): array
    {
        $channelId = (int) auth()->user()->current_channel_id;

        return [
            'name' => ['nullable', 'string', 'max:191'],
            'email' => [
                'required',
                'string',
                'email',
                'max:191',
                Rule::unique('newsletter_subscribers', 'email')
                    ->where(fn ($query) => $query->where('channel_id', $channelId))
                    ->ignore((int) $this->route('id')),
            ],
            'status' => ['required', Rule::in(['subscribed', 'unsubscribed'])],
        ];
    }
}
