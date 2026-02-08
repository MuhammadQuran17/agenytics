<?php

namespace App\Services\AiAgent\NeuronAi;

use App\Http\Requests\Api\AiAgent\AiAgentSendMessageRequest;
use App\Services\AiAgent\AiAgentInterface;

/**
 * https://www.neuron-ai.dev/
 */
class NeuronAiAgent implements AiAgentInterface
{
    public function sendMessage(AiAgentSendMessageRequest $request): array
    {
        throw new \Exception('Not implemented yet');
    }
}