<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Hashtag extends Model
{
    protected $fillable = ['name', 'slug', 'videos_count'];

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'hashtag_video');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Leírásból kinyeri és elmenti a hashtageket, visszaadja az ID-kat
     */
    public static function syncFromDescription(string $text): array
    {
        preg_match_all('/#([a-zA-ZÀ-öø-ÿ0-9_]+)/u', $text, $matches);

        $ids = [];
        foreach (array_unique($matches[1]) as $tag) {
            $slug    = Str::lower(Str::ascii($tag));
            $hashtag = self::firstOrCreate(
                ['slug' => $slug],
                ['name' => $tag, 'slug' => $slug]
            );
            $ids[] = $hashtag->id;
        }

        return $ids;
    }

    /**
     * Trending hashtagek (legtöbb videóval)
     */
    public static function trending(int $limit = 10)
    {
        return self::where('videos_count', '>', 0)
            ->orderByDesc('videos_count')
            ->limit($limit)
            ->get();
    }
}
