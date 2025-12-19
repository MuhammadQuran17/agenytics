<?php

namespace App\Services\AiAgent\N8n;

use App\Http\Requests\Api\AiAgent\AiAgentSendMessageRequest;
use App\Services\AiAgent\AiAgentInterface;
use Illuminate\Support\Facades\Http;

class N8nAiAgent implements AiAgentInterface
{
    public function sendMessage(AiAgentSendMessageRequest $request, ?string $threadId): array
    {
        if (config('ai_responses.is_fake_response_enabled')) {
            $this->fakeResponse('success');
        }
        
        logger()->debug('request', $request->toArray());

        $response = Http::timeout(300)
            ->withBasicAuth(
                config('services.n8n.basic_auth.username'),
                config('services.n8n.basic_auth.password')
            )
            ->post(config('services.n8n.url'), [
                'sessionId' => $request->sessionId,
                'threadId' => $threadId,
                'chatInput' => $request->message,
            ]);

        logger()->debug('mm', $response->json());

        return $response->json();
    }

    /**
     * Fake response for testing purposes
     */
    private function fakeResponse(?string $type = 'success'): \Illuminate\Http\Client\Factory
    {
        return Http::fake([
            config('services.n8n.url') => Http::response(config("ai_responses.fake_responses.$type")),
        ]);
    }
}
