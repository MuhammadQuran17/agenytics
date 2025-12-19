<?php

namespace App\Services\AiChat\Thread;

use App\Models\UserChat;
use Illuminate\Support\Facades\Auth;

class AiChatThread
{
    public function getThreadId(string $sessionId, ?string $passedUserId = null): ?string
    {
        $userId = $passedUserId ?? Auth::user()->id;

        return UserChat::where('session_id', $sessionId)->where('user_id', $userId)->first('thread_id')?->thread_id;
    }

    public function updateThreadIfChanged(
        ?string $currentThreadId,
        string $sessionId,
        ?string $newThreadId,
        ?string $passedUserId = null
    ): void {

        $userId = $passedUserId ?? Auth::user()->id;

        if ($currentThreadId != $newThreadId) {
            UserChat::where('session_id', $sessionId)->where('user_id', $userId)->update(['thread_id' => $newThreadId]);
        }
    }
}
