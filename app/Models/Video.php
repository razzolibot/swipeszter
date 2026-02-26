<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'original_path',
        'hls_path',
        'thumbnail_path',
        'duration',
        'views_count',
        'likes_count',
        'comments_count',
        'status',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'duration' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->latest();
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeReady($query)
    {
        return $query->where('status', 'ready')->where('is_public', true);
    }

    public function scopeFeed($query)
    {
        return $query->ready()->latest()->with('user');
    }

    // ─── Accessors ───────────────────────────────────────────────

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path
            ? Storage::url($this->thumbnail_path)
            : null;
    }

    public function getHlsUrlAttribute(): ?string
    {
        return $this->hls_path
            ? Storage::url($this->hls_path)
            : null;
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isLikedBy(?User $user): bool
    {
        if (! $user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
