<?php

use App\Jobs\ProcessAiChatMessage;
use App\Models\ChatHistory;
use App\Models\UserChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

describe('Async Message Processing - Job Dispatch', function () {
    it('does not dispatch job when user has no free prompts', function () {
        Queue::fake();

        $user = makeUserWithPrompts(0);

        $this->actingAs($user);

        postChatRequest($this)->assertStatus(402);

        Queue::assertNothingPushed();
    });
});

describe('Async Message Processing - Job Execution', function () {
    it('creates a failed chat history record when job failed() method is called', function () {
        $user = makeUserWithPrompts(5);
        $userChat = UserChat::factory()->create(['user_id' => $user->id]);

        // Create a job instance
        $job = new ProcessAiChatMessage(
            [
                'sessionId' => $userChat->session_id,
                'message' => 'Test message',
            ],
            (string) $user->id
        );

        // Mock the job property with a job ID
        $mockJob = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $mockJob->shouldReceive('getJobId')->andReturn('mock-job-123');
        $job->setJob($mockJob);

        // Call the failed method
        $exception = new \Exception('Test exception message');
        $job->failed($exception);

        // Verify that a failed chat history was created
        $failedHistory = ChatHistory::where('job_id', 'mock-job-123')
            ->where('job_status', 'failed')
            ->first();

        expect($failedHistory)->not->toBeNull();
        expect($failedHistory->role)->toBe('assistant');
        expect($failedHistory->error)->toBe('Test exception message');
        expect($failedHistory->user_chat_session_id)->toBe($userChat->session_id);
    });
});

describe('Chat Status Polling', function () {
    it('returns failed status with error message when job has failed', function () {
        $user = makeUserWithPrompts(5);
        $userChat = UserChat::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $jobId = 'test-job-failed';

        // Create a failed chat history
        ChatHistory::create([
            'user_chat_session_id' => $userChat->session_id,
            'job_id' => $jobId,
            'job_status' => 'failed',
            'role' => 'assistant',
            'error' => 'API connection failed',
        ]);

        $response = $this->postJson(route('chat.status'), ['jobId' => $jobId]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'failed',
            'error' => 'API connection failed',
        ]);
    });

    it('returns processing status when job is still running', function () {
        $user = makeUserWithPrompts(5);
        $userChat = UserChat::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $jobId = 'test-job-processing';

        // Create a processing chat history
        ChatHistory::create([
            'user_chat_session_id' => $userChat->session_id,
            'job_id' => $jobId,
            'job_status' => 'processing',
            'role' => 'user',
            'user_input' => 'Test message',
        ]);

        $response = $this->postJson(route('chat.status'), ['jobId' => $jobId]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'processing',
        ]);
    });

    it('returns completed status with response when job is done', function () {
        $user = makeUserWithPrompts(5);
        $userChat = UserChat::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $jobId = 'test-job-completed';

        // Create a completed chat history
        ChatHistory::create([
            'user_chat_session_id' => $userChat->session_id,
            'job_id' => $jobId,
            'job_status' => 'completed',
            'role' => 'assistant',
            'message' => 'Test response message',
            'response_type' => 'message',
        ]);

        $response = $this->postJson(route('chat.status'), ['jobId' => $jobId]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'status',
            'response',
        ]);
        expect($response->json('status'))->toBe('completed');
    });
});
