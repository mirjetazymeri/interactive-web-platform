<?php

namespace App\Http\Requests\Admin\Assistant;

use App\Models\AssistantAiSetting;
use App\Services\Assistant\Ai\AiProviderRegistry;
use App\Services\Assistant\Ai\AssistantAiSettings;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssistantAiSettingsRequest extends FormRequest
{
    public const CURRENCIES = ['EUR', 'USD', 'GBP'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'disable_on_limit' => $this->boolean('disable_on_limit'),
            'remove_api_key' => $this->boolean('remove_api_key'),
            'provider' => $this->input('provider') ?: null,
            'model' => trim((string) $this->input('model')) ?: null,
            'endpoint_url' => trim((string) $this->input('endpoint_url')) ?: null,
            'strategy' => $this->input('strategy') ?: 'deterministic',
        ]);
    }

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'provider' => ['nullable', 'string', Rule::in(array_keys(app(AiProviderRegistry::class)->providers()))],
            'model' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:\/@-]*$/'],
            'endpoint_url' => ['nullable', 'url:https', 'max:255'],
            'strategy' => ['required', Rule::in(AssistantAiSetting::STRATEGIES)],
            'api_key' => ['nullable', 'string', 'min:8', 'max:500'],
            'remove_api_key' => ['boolean'],
            'monthly_request_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'monthly_token_limit' => ['nullable', 'integer', 'min:1', 'max:1000000000000'],
            'monthly_spend_limit' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'spend_currency' => ['required', Rule::in(self::CURRENCIES)],
            'disable_on_limit' => ['boolean'],
        ];
    }

    /** Fail closed: nothing beyond deterministic answers can be chosen without an installed provider adapter. */
    public function after(): array
    {
        return [function (\Illuminate\Validation\Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $provider = $this->input('provider');
            if (! in_array($this->input('strategy'), app(AiProviderRegistry::class)->availableStrategies($provider), true)) {
                $validator->errors()->add('strategy', __('assistant.ai_strategy_unavailable'));
            }
            if ($this->boolean('enabled') && ! app(AssistantAiSettings::class)->canEnable($provider)) {
                $validator->errors()->add('enabled', __('assistant.ai_enable_unavailable'));
            }
        }];
    }

    /** On errors the form is shown again without the key: a secret is never flashed into the session. */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, redirect($this->getRedirectUrl())
            ->withInput($this->except(['api_key', '_token']))
            ->withErrors($validator->errors(), $this->errorBag));
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
