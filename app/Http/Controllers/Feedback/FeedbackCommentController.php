<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackCommentController extends Controller
{
    public function saveComment(
        Request $request,
        Feedback $feedback,
        #[CurrentUser] User $user
    ): JsonResponse {

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $comment = FeedbackComment::create([
            'user_id' => $user->id,
            'feedback_id' => $feedback->id,
            'message' => $request->message,
        ]);

        return response()->json([
            'message' => 'Comment added successfully!',
            'comment' => $comment->load('user'),
        ]);
    }

    public function listOfComments(Feedback $feedback): JsonResponse
    {
        return response()->json(
            $feedback->comments()->with('user')->latest()->get()
        );
    }
}
