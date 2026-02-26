<?php

namespace App\Jobs;

use App\Models\Video;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 2;

    public function __construct(private Video $video) {}

    public function handle(): void
    {
        $this->video->update(['status' => 'processing']);

        try {
            $inputPath  = Storage::disk('public')->path($this->video->original_path);
            $outputDir  = Storage::disk('public')->path("videos/hls/{$this->video->id}");
            $thumbDir   = Storage::disk('public')->path("videos/thumbnails");

            @mkdir($outputDir, 0755, true);
            @mkdir($thumbDir, 0755, true);

            // ── Időtartam kinyerése FFProbe-bal ──────────────────
            $ffprobe  = FFProbe::create();
            $duration = (int) $ffprobe->format($inputPath)->get('duration', 0);

            // ── Thumbnail generálás (3. másodpercnél) ────────────
            $thumbPath = "{$thumbDir}/{$this->video->id}.jpg";
            $ffmpeg    = FFMpeg::create(['timeout' => 300]);
            $video     = $ffmpeg->open($inputPath);

            $video->frame(TimeCode::fromSeconds(min(3, max(0, $duration - 1))))
                ->save($thumbPath);

            // ── HLS transzkódolás (720p) ──────────────────────────
            $hlsPlaylist = "{$outputDir}/index.m3u8";

            // Shell paranccsal hívjuk az ffmpeg-et HLS-hez
            // (a php-ffmpeg library HLS támogatása limitált)
            $ffmpegBin = config('app.ffmpeg_bin', 'ffmpeg');
            $cmd = escapeshellcmd(implode(' ', [
                $ffmpegBin,
                '-i', escapeshellarg($inputPath),
                '-vf', 'scale=-2:720',
                '-c:v', 'libx264',
                '-crf', '23',
                '-preset', 'fast',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-hls_time', '4',
                '-hls_list_size', '0',
                '-hls_segment_filename', escapeshellarg("{$outputDir}/seg%03d.ts"),
                '-f', 'hls',
                escapeshellarg($hlsPlaylist),
            ]));

            exec($cmd . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException('FFmpeg HLS transzkódolás sikertelen: ' . implode("\n", $output));
            }

            // ── Paths mentése DB-be ───────────────────────────────
            $this->video->update([
                'status'         => 'ready',
                'hls_path'       => "videos/hls/{$this->video->id}/index.m3u8",
                'thumbnail_path' => "videos/thumbnails/{$this->video->id}.jpg",
                'duration'       => $duration,
            ]);

            // Eredeti fájl törlése (ha akarod — helytakarékos)
            // Storage::disk('public')->delete($this->video->original_path);

        } catch (\Throwable $e) {
            Log::error("ProcessVideo hiba (video #{$this->video->id}): " . $e->getMessage());
            $this->video->update(['status' => 'failed']);
            throw $e;
        }
    }
}
