<?php

namespace App\Services\AiChat;

use App\Exceptions\AiChatJobLimitExceededException;
use App\Http\Requests\Api\AiAgent\AiAgentSendMessageRequest;
use App\Jobs\ProcessAiChatMessage;
use App\Services\AiChat\Contracts\MessageProcessingContract;
use App\Services\AiChat\History\AiChatHistory;
use App\Services\Subscription\Exceptions\NoFreePromptsAndNoSubscriptionException;
use App\Services\Subscription\SubscriptionTrackUsage;
use App\Services\Subscription\SubscriptionValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;

class DefaultMessageProcessing extends MessageProcessingContract
{
    public function __construct(
        private SubscriptionValidation $subscriptionValidation,
        private AiChatHistory $aiChatHistory,
        private UserJobLimiter $userJobLimiter,
        private SubscriptionTrackUsage $subscriptionTrackUsage,
    ) {}

    public function processMessage(AiAgentSendMessageRequest $request): array
    {
        $this->subscriptionTrackUsage->zeroingPurchasedPromptsIfExpired();

        if (! $this->subscriptionValidation->hasAvailablePrompts()) {
            throw new NoFreePromptsAndNoSubscriptionException;
        }

        $userId = Auth::user()->id;

        if (! $this->userJobLimiter->canDispatchJob($userId)) {
            throw new AiChatJobLimitExceededException;
        }

        $jobId = Bus::dispatch(new ProcessAiChatMessage($request->toArray(), $userId));

        $this->aiChatHistory->saveUserInput($request->message, $request->sessionId, $jobId);

        return [
            'jobId' => (string) $jobId,
            'sessionId' => $request->sessionId,
        ];
    }
}
