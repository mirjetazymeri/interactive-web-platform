<?php

namespace App\Http\Requests\Admin\NewsletterSubscriber;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreNewsletterSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
            'status' => $this->input('status', 'subscribed'),
        ]);
    }

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
                Rule::unique('newsletter_subscribers', 'email')->where(
                    fn ($query) => $query->where('channel_id', $channelId)
                ),
            ],
            'status' => ['required', Rule::in(['subscribed', 'unsubscribed'])],
        ];
    }
}
