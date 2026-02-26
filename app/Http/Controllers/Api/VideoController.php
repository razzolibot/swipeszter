<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideo;
use App\Models\Hashtag;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    // GET /api/videos — For You feed
    public function index(Request $request): JsonResponse
    {
        $videos = Video::feed()
            ->with('hashtags')
            ->paginate(5);

        return response()->json([
            'data' => $videos->map(fn ($v) => $this->formatVideo($v, $request->user())),
            'next_page' => $videos->nextPageUrl(),
        ]);
    }

    // GET /api/videos/{video}
    public function show(Request $request, Video $video): JsonResponse
    {
        if (! $video->is_public && $video->user_id !== optional($request->user())->id) {
            return response()->json(['message' => 'Nem elérhető.'], 403);
        }

        return response()->json($this->formatVideo($video->load('user'), $request->user()));
    }

    // POST /api/videos — videó feltöltés
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'video'       => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo', 'max:512000'],
            'title'       => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $path = $request->file('video')->store('videos/original', 'public');

        $description = $request->input('description');

        $video = Video::create([
            'user_id'       => $request->user()->id,
            'title'         => $request->input('title'),
            'description'   => $description,
            'original_path' => $path,
            'status'        => 'pending',
        ]);

        // Hashtagek kinyerése és mentése
        if ($description) {
            $hashtagIds = Hashtag::syncFromDescription($description);
            $video->hashtags()->sync($hashtagIds);
            Hashtag::whereIn('id', $hashtagIds)->increment('videos_count');
        }

        // Átadjuk a queue-nak feldolgozásra (FFmpeg → HLS)
        ProcessVideo::dispatch($video);

        return response()->json($this->formatVideo($video, $request->user()), 201);
    }

    // DELETE /api/videos/{video}
    public function destroy(Request $request, Video $video): JsonResponse
    {
        if ($video->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Nincs jogosultságod.'], 403);
        }

        if ($video->original_path) Storage::disk('public')->deleteDirectory("videos/original/{$video->id}");
        if ($video->hls_path) Storage::disk('public')->deleteDirectory("videos/hls/{$video->id}");

        $video->delete();

        return response()->json(['message' => 'Videó törölve.']);
    }

    // POST /api/videos/{video}/view
    public function recordView(Request $request, Video $video): JsonResponse
    {
        $request->validate([
            'watched_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $video->views()->create([
            'user_id'         => optional($request->user())->id,
            'ip'              => $request->ip(),
            'watched_percent' => $request->input('watched_percent', 0),
        ]);

        $video->incrementViews();

        return response()->json(['ok' => true]);
    }

    private function formatVideo(Video $video, ?object $user): array
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
            'hashtags'       => $video->relationLoaded('hashtags')
                ? $video->hashtags->map(fn($h) => ['id' => $h->id, 'name' => $h->name, 'slug' => $h->slug])
                : [],
            'user' => [
                'id'       => $video->user->id,
                'name'     => $video->user->name,
                'username' => $video->user->username,
                'avatar'   => $video->user->avatar,
            ],
        ];
    }
}
