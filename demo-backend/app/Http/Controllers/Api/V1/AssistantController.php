<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssistantMessageRequest;
use App\Services\Assistant\AssistantEngine;
use Illuminate\Http\Request;

/**
 * Public Smart Assistant API. Channel and language come from the usual
 * X-Channel / X-Locale headers. Answers are rule-based and free of any
 * external AI service.
 */
class AssistantController extends Controller
{
    public function __construct(private AssistantEngine $engine) {}

    // GET /api/v1/assistant/bootstrap
    public function bootstrap(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->engine->bootstrap($this->channelId(), app()->getLocale(), $this->preview($request))]);
    }

    // POST /api/v1/assistant/message  {message, intent_id?, context?}
    public function message(AssistantMessageRequest $request)
    {
        $channelId = $this->channelId();
        $locale = app()->getLocale();
        $preview = $this->preview($request);
        if (! $preview && ! $this->engine->enabled($channelId, $locale)) {
            return response()->json(['success' => false, 'message' => 'Assistant unavailable.'], 404);
        }
        $data = $request->validated();
        $reply = $this->engine->reply($channelId, $locale, (string) ($data['message'] ?? ''), (array) ($data['context'] ?? []),
            isset($data['intent_id']) ? (int) $data['intent_id'] : null, false, ! $preview);

        return response()->json(['success' => true, 'data' => $reply])->header('Cache-Control', 'no-store');
    }

    /**
     * A preview request from a trusted frontend server: the assistant answers while it is still
     * hidden from visitors. Only with ASSISTANT_PREVIEW_TOKEN configured (it is empty by default).
     */
    private function preview(Request $request): bool
    {
        $token = (string) config('assistant.preview_token', '');
        $given = $request->header('X-Assistant-Preview');

        return strlen($token) >= 32 && is_string($given) && hash_equals($token, $given);
    }

    private function channelId(): int
    {
        $channelId = app('currentChannel')?->id;
        abort_if(! $channelId, 400, 'Channel not resolved');

        return (int) $channelId;
    }
}
