<?php

namespace App\Http\Requests\Api\V1;

use App\Services\Assistant\AssistantEngine;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AssistantMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('message'))) {
            // Control characters never belong in a question.
            $this->merge(['message' => trim(preg_replace('/[^\P{C}]+/u', ' ', $this->input('message')) ?? '')]);
        }
    }

    public function rules(): array
    {
        return [
            'message' => ['required_without:intent_id', 'nullable', 'string', 'max:'.(int) config('assistant.max_length', 300)],
            'intent_id' => ['nullable', 'integer', 'min:1'],
            'context' => ['nullable', 'array:intent_id,entity,entities,excluded,turn,language,act'],
            // Services under discussion and services the visitor ruled out; each checked again by the engine.
            'context.entities' => ['nullable', 'array', 'max:5'],
            'context.entities.*' => ['string', 'max:200', 'regex:'.AssistantEngine::ENTITY_PATTERN],
            'context.excluded' => ['nullable', 'array', 'max:5'],
            'context.excluded.*' => ['string', 'max:200', 'regex:'.AssistantEngine::ENTITY_PATTERN],
            'context.intent_id' => ['nullable', 'integer', 'min:1'],
            'context.entity' => ['nullable', 'string', 'max:200', 'regex:'.AssistantEngine::ENTITY_PATTERN],
            'context.turn' => ['nullable', 'integer', 'min:0', 'max:100'],
            // The conversation's language and the last request type; checked again by the engine.
            'context.language' => ['nullable', 'string', 'regex:'.AssistantEngine::LANGUAGE_PATTERN],
            'context.act' => ['nullable', 'string', Rule::in(AssistantEngine::ACTS)],
        ];
    }

    /** Always answer with JSON, also to clients that did not ask for it. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false, 'message' => 'Invalid request.', 'errors' => $validator->errors(),
        ], 422));
    }
}
