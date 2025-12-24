<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Middleware\FeedbackAdminMiddleware;
use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function index(): Response
    {
        $feedbacks = Feedback::withCount(['votes as upvotes_count', 'comments'])
            ->with('user:id,name')
            ->orderByDesc('upvotes_count')
            ->paginate(config('feedback.feedbacks_per_page'))
            ->through(fn ($feedback) => $this->mapReturnFeedback($feedback, true));

        return Inertia::render('FeedbackPage', [
            'feedbacks' => $feedbacks,
            'canManageFeedback' => app(FeedbackAdminMiddleware::class)->isAdminUser(),
        ]);
    }

    private function mapReturnFeedback($feedback, $includeUserVote = false)
    {
        $result = [
            'id' => $feedback->id,
            'title' => $feedback->title,
            'description' => $feedback->description,
            'created_at' => $feedback->created_at,
            'status' => $feedback->status,
            'upvotes_count' => $feedback->votes()->count(),
            'comments_count' => $feedback->comments_count,
            'author' => optional($feedback->user)->name ?? 'Anonymous',
            'tags' => $feedback->tags ?? [],
        ];

        if ($includeUserVote && Auth::check()) {
            $result['user_has_voted'] = $feedback->votes()->where('user_id', Auth::id())->exists();
        }

        return $result;
    }

    public function filterData(Request $request): JsonResponse
    {
        // Validate input parameters to prevent SQL injection and XSS
        $validated = $request->validate([
            'status' => 'nullable|string|in:planned,in_progress,completed,closed',
            'tag' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
            'search' => 'nullable|string|max:255',
            'time' => 'nullable|string|in:today,this_week,this_month,this_year',
        ]);

        $query = Feedback::withCount(['votes as upvotes_count', 'comments'])
            ->with('user:id,name');

        $query->when($validated['status'] ?? null, function ($q, $status) {
            return $q->where('status', $status);
        });

        $query->when($validated['tag'] ?? null, function ($q, $tag) {
            return $q->whereJsonContains('tags', $tag);
        });

        $query->when($validated['search'] ?? null, function ($q, $search) {
            return $q->where('title', 'ILIKE', "%{$search}%");
        });

        $query->when($validated['time'] ?? null, function ($q, $time) {
            $currentTime = now();

            return match ($time) {
                'today' => $q->whereDate('created_at', $currentTime->copy()->toDateString()),
                'this_week' => $q->where('created_at', '>=', $currentTime->copy()->subDays(7)),
                'this_month' => $q->whereBetween('created_at', [$currentTime->copy()->startOfMonth(), $currentTime->copy()->endOfMonth()]),
                'this_year' => $q->whereYear('created_at', $currentTime->copy()->year),
                default => $q,
            };
        });

        $feedbacks = $query->latest()
            ->paginate(config('feedback.feedbacks_per_page'))
            ->through(fn ($feedback) => $this->mapReturnFeedback($feedback, true));

        return response()->json($feedbacks);
    }

    public function updateOrInsertNewFeedback(Request $request, ?Feedback $feedback = null): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $feedback = Feedback::updateOrCreate(
            ['user_id' => Auth::id(), 'title' => $validated['title']],
            [
                'description' => $validated['description'],
                'tags' => $validated['tags'],
            ]
        );

        $message = $feedback->wasRecentlyCreated ? 'Feedback created!' : 'Feedback updated!';

        return redirect()->route('feedback.list')->with('success', $message);
    }

    public function updateStatus(Request $request, Feedback $feedback): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:planned,in_progress,completed,closed',
        ]);

        $feedback->update(['status' => $validated['status']]);

        return response()->json(['status' => $feedback->status]);
    }

    public function edit(Feedback $feedback): Response
    {
        return Inertia::render('FeedbackEdit', [
            'feedback' => [
                'id' => $feedback->id,
                'title' => $feedback->title,
                'description' => $feedback->description,
                'tags' => $feedback->tags ?? [],
                'status' => $feedback->status,
            ],
            'canEdit' => app(FeedbackAdminMiddleware::class)->isAdminUser(),
        ]);
    }

    public function update(Request $request, Feedback $feedback): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'nullable|string|in:planned,in_progress,completed,closed',
        ]);

        $feedback->update($validated);

        return response()->json(['message' => 'Feedback updated successfully!', 'feedback' => $feedback]);
    }

    public function destroy(Feedback $feedback): JsonResponse
    {
        $feedback->delete();

        return response()->json(['message' => 'Feedback deleted successfully!']);
    }

    public function vote(Request $request, Feedback $feedback, #[CurrentUser] User $user): JsonResponse
    {
        $voteAttributes = [
            'user_id' => $user->id,
            'feedback_id' => $feedback->id,
        ];

        $change = 0;
        $direction = $request->input('direction', 'up');

        if ($direction === 'up') {
            $vote = FeedbackVote::firstOrCreate($voteAttributes);

            if ($vote->wasRecentlyCreated) {
                $change = 1;
            }
        } elseif ($direction === 'down') {
            $deletedCount = FeedbackVote::where($voteAttributes)->delete();

            if ($deletedCount > 0) {
                $change = -1;
            }
        }

        return response()->json(['change' => $change]);
    }

    public function show(Feedback $feedback): Response
    {
        $feedback->load(['comments.user', 'votes']);

        $mappedFeedback = $this->mapReturnFeedback($feedback);

        // Check if current user has voted
        $userHasVoted = false;
        if (Auth::check()) {
            $userHasVoted = $feedback->votes()->where('user_id', Auth::id())->exists();
        }

        // Get vote statistics
        $totalVotes = $feedback->votes()->count();

        return Inertia::render('FeedbackDetail', [
            'feedback' => $mappedFeedback,
            'userHasVoted' => $userHasVoted,
            'totalVotes' => $totalVotes,
            'canManageFeedback' => app(FeedbackAdminMiddleware::class)->isAdminUser(),
            'comments' => $feedback->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'message' => $comment->message,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                ];
            }),
        ]);
    }

    public function comment(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $feedback->comments()->create([
            'message' => $validated['message'],
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Comment added!');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        try {
            $image = $request->file('upload');
            $path = $image->store('feedback_images', 'public');
            $url = Storage::url($path);

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
