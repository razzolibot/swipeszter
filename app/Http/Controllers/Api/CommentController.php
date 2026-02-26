<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Video;
use App\Notifications\CommentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // GET /api/videos/{video}/comments
    public function index(Video $video): JsonResponse
    {
        $comments = $video->comments()
            ->with(['user', 'replies.user'])
            ->paginate(20);

        return response()->json($comments);
    }

    // POST /api/videos/{video}/comments
    public function store(Request $request, Video $video): JsonResponse
    {
        $validated = $request->validate([
            'content'   => ['required', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        $comment = $video->comments()->create([
            'user_id'   => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content'   => $validated['content'],
        ]);

        $video->increment('comments_count');

        // Értesítés a videó tulajdonosának
        if ($video->user_id !== $request->user()->id) {
            $video->user->notify(new CommentNotification($request->user(), $comment->load('video')));
        }

        return response()->json($comment->load('user'), 201);
    }

    // DELETE /api/comments/{comment}
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Nincs jogosultságod.'], 403);
        }

        $comment->video->decrement('comments_count');
        $comment->delete();

        return response()->json(['message' => 'Komment törölve.']);
    }
}
