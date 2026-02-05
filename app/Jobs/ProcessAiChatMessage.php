<?php

namespace App\Jobs;

use App\Http\Requests\Api\AiAgent\AiAgentSendMessageRequest;
use App\Models\User;
use App\Services\AiAgent\N8n\N8nAiAgent;
use App\Services\AiChat\History\AiChatHistory;
use App\Services\AiChat\Thread\AiChatThread;
use App\Services\Subscription\SubscriptionTrackUsage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiChatMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $request,
        private string $userId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        AiChatThread $aiChatThread,
        N8nAiAgent $N8nAiAgent,
        AiChatHistory $aiChatHistory,
        SubscriptionTrackUsage $subscriptionTrackUsage,
    ): void {

        $user = User::findOrFail($this->userId);

        /** @var ?string $chatThreadId */
        $chatThreadId = $aiChatThread->getThreadId(
            $this->request['sessionId'],
            $user->id
        );

        $response = $N8nAiAgent->sendMessage(
            new AiAgentSendMessageRequest($this->request),
            $chatThreadId
        );

        Log::info('N8N response', ['response' => $response]);

        // update thread id if it was created in N8N
        $aiChatThread->updateThreadIfChanged(
            $chatThreadId,
            $this->request['sessionId'],
            Arr::get($response, 'threadId'),
            $user->id,
        );

        $aiChatHistory->saveAssistantResponse(
            $response,
            $this->request['sessionId'],
            $this->job->getJobId(),
        );

        if (! Arr::get($response, 'error')) $subscriptionTrackUsage->trackUsage($user);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessAiChatMessage job failed', [
            'job_id' => $this->job?->getJobId(),
            'session_id' => $this->request['sessionId'] ?? null,
            'user_id' => $this->userId,
            'exception' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        // Save failed status to ChatHistory so frontend can detect it
        if ($this->job && isset($this->request['sessionId'])) {
            \App\Models\ChatHistory::create([
                'user_chat_session_id' => $this->request['sessionId'],
                'job_id' => $this->job->getJobId(),
                'job_status' => 'failed',
                'role' => 'assistant',
                'error' => $exception?->getMessage() . ' Please try again later.' ?? 'An unexpected error occurred while processing your message. Please try again later.',
            ]);
        }
    }
}
