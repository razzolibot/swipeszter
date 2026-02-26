<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\FollowNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    // POST /api/users/{user}/follow — toggle follow
    public function toggle(Request $request, User $user): JsonResponse
    {
        $me = $request->user();

        if ($me->id === $user->id) {
            return response()->json(['message' => 'Magadat nem követheted.'], 422);
        }

        if ($me->isFollowing($user)) {
            $me->following()->detach($user->id);
            $following = false;
        } else {
            $me->following()->attach($user->id);
            $following = true;

            // Értesítés az új követettnek
            $user->notify(new FollowNotification($me));
        }

        return response()->json([
            'following'       => $following,
            'followers_count' => $user->fresh()->followers()->count(),
        ]);
    }
}
