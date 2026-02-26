<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // GET /api/users/{username}
    public function show(Request $request, string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        return response()->json([
            'id'              => $user->id,
            'name'            => $user->name,
            'username'        => $user->username,
            'avatar'          => $user->avatar,
            'bio'             => $user->bio,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'videos_count'    => $user->videos()->where('status', 'ready')->count(),
            'is_following'    => optional($request->user())->isFollowing($user) ?? false,
            'videos'          => $user->videos()->where('status', 'ready')
                ->latest()
                ->take(12)
                ->get()
                ->map(fn ($v) => [
                    'id'            => $v->id,
                    'thumbnail_url' => $v->thumbnail_url,
                    'views_count'   => $v->views_count,
                    'likes_count'   => $v->likes_count,
                ]),
        ]);
    }

    // PATCH /api/profile
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => ['sometimes', 'string', 'max:100'],
            'bio'    => ['sometimes', 'nullable', 'string', 'max:200'],
            'avatar' => ['sometimes', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return response()->json([
            'id'       => $user->id,
            'name'     => $user->name,
            'username' => $user->username,
            'avatar'   => $user->avatar,
            'bio'      => $user->bio,
        ]);
    }
}
