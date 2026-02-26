<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    // POST /api/videos/{video}/like — toggle like
    public function toggle(Request $request, Video $video): JsonResponse
    {
        $user = $request->user();
        $like = $video->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $video->decrement('likes_count');
            $liked = false;
        } else {
            $video->likes()->create(['user_id' => $user->id]);
            $video->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'liked'       => $liked,
            'likes_count' => $video->fresh()->likes_count,
        ]);
    }
}
