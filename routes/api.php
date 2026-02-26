<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\VideoController;
use Illuminate\Support\Facades\Route;

// ── Auth ─────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Videók ───────────────────────────────────────────────────
    Route::post('/videos/{video}/like',    [LikeController::class, 'toggle']);
    Route::post('/videos/{video}/view',    [VideoController::class, 'recordView']);
    Route::post('/videos/{video}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}',   [CommentController::class, 'destroy']);
    Route::post('/videos',                 [VideoController::class, 'store']);
    Route::delete('/videos/{video}',       [VideoController::class, 'destroy']);

    // ── Profil ───────────────────────────────────────────────────
    Route::patch('/profile',              [ProfileController::class, 'update']);
    Route::post('/users/{user}/follow',   [FollowController::class, 'toggle']);
});

// ── Publikus ─────────────────────────────────────────────────────
Route::get('/videos',              [VideoController::class, 'index']);
Route::get('/videos/{video}',      [VideoController::class, 'show']);
Route::get('/videos/{video}/comments', [CommentController::class, 'index']);
Route::get('/users/{username}',    [ProfileController::class, 'show']);
