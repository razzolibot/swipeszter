<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HashtagController extends Controller
{
    // GET /api/hashtags/trending
    public function trending(): JsonResponse
    {
        return response()->json(Hashtag::trending(20));
    }

    // GET /api/hashtags/{slug}
    public function show(string $slug): JsonResponse
    {
        $hashtag = Hashtag::where('slug', $slug)->firstOrFail();

        return response()->json([
            'id'           => $hashtag->id,
            'name'         => $hashtag->name,
            'slug'         => $hashtag->slug,
            'videos_count' => $hashtag->videos_count,
        ]);
    }

    // GET /api/hashtags/{slug}/videos
    public function videos(Request $request, string $slug): JsonResponse
    {
        $hashtag = Hashtag::where('slug', $slug)->firstOrFail();

        $videos = $hashtag->videos()
            ->with(['user', 'hashtags'])
            ->where('status', 'ready')
            ->where('is_public', true)
            ->latest()
            ->paginate(10);

        return response()->json([
            'hashtag'   => ['id' => $hashtag->id, 'name' => $hashtag->name, 'slug' => $hashtag->slug, 'videos_count' => $hashtag->videos_count],
            'data'      => $videos->map(fn($v) => $this->formatVideo($v, $request->user())),
            'next_page' => $videos->nextPageUrl(),
        ]);
    }

    private function formatVideo($video, $user): array
    {
        return [
            'id'             => $video->id,
            'title'          => $video->title,
            'description'    => $video->description,
            'hls_url'        => $video->hls_url,
            'thumbnail_url'  => $video->thumbnail_url,
            'duration'       => $video->duration,
            'views_count'    => $video->views_count,
            'likes_count'    => $video->likes_count,
            'comments_count' => $video->comments_count,
            'status'         => $video->status,
            'is_liked'       => $video->isLikedBy($user),
            'created_at'     => $video->created_at,
            'hashtags'       => $video->hashtags->map(fn($h) => ['id' => $h->id, 'name' => $h->name, 'slug' => $h->slug]),
            'user' => [
                'id'       => $video->user->id,
                'name'     => $video->user->name,
                'username' => $video->user->username,
                'avatar'   => $video->user->avatar,
            ],
        ];
    }
}
