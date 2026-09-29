<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VideoAsset;
use Illuminate\Support\Str;

class PlaybackFixture extends Command
{
    protected $signature = 'playback:fixture {videoAsset : The video asset id (required)}';
    protected $description = 'Generate a realistic playback overlay fixture JSON for a video asset';

    public function handle()
    {
        $videoAssetId = (int)$this->argument('videoAsset');
        $videoAsset = VideoAsset::find($videoAssetId);
        if (! $videoAsset) {
            $this->error("VideoAsset id={$videoAssetId} not found.");
            return 1;
        }
        if ($videoAsset->width === null || $videoAsset->height === null || $videoAsset->fps === null || $videoAsset->duration === null || $videoAsset->duration <= 0) {
            $this->error("VideoAsset id={$videoAssetId} is missing width/height/fps/duration. Run: php artisan video:probe {$videoAssetId}");
            return 1;
        }
        $w = $videoAsset->width; $h = $videoAsset->height; $fps = $videoAsset->fps; $duration = $videoAsset->duration;
        $dir = storage_path("app/playback/{$videoAssetId}");
        @mkdir($dir, 0755, true);

        $stride = 5;
        $frames = [];
        $time = 0.0;
        $trackIds = [3, 8, 11, 14]; // persistent drifting people
        $phoneIds = [7, 19]; // brief appearances
        $t = 0.0;
        while ($t <= $duration) {
            $boxes = [];
            foreach ($trackIds as $tid) {
                $cx = 320 + ((int)($t * 15 + $tid * 137)) % 960;
                $cy = 240 + ((int)($t * 10 + $tid * 89)) % 540;
                $boxes[] = [
                    'id' => $tid,
                    'cls' => 'person',
                    'conf' => max(0.4, min(0.98, 0.82 + (sin($t * 0.4 + $tid) * 0.08))),
                    'xyxy' => [max(0, $cx - 90), max(0, $cy - 180), min($w, $cx + 90), min($h, $cy + 180)],
                ];
            }
            // Phone appearances lasting 3-8s
            if ($t >= 22.0 && $t <= 28.5) {
                $boxes[] = [
                    'id' => 7,
                    'cls' => 'cell phone',
                    'conf' => max(0.4, min(0.98, 0.78 + (sin($t * 2) * 0.05))),
                    'xyxy' => [max(0, 455 + (int)(sin($t * 3) * 30)), max(0, 300 + (int)(cos($t * 2) * 10)), min($w, 500 + (int)(sin($t * 3) * 30)), min($h, 350 + (int)(cos($t * 2) * 10))],
                ];
            }
            if ($t >= 65.0 && $t <= 71.2) {
                $boxes[] = [
                    'id' => 19,
                    'cls' => 'cell phone',
                    'conf' => max(0.4, min(0.98, 0.81 + (sin($t * 1.8) * 0.04))),
                    'xyxy' => [max(0, 860 + (int)(cos($t * 3) * 25)), max(0, 410 + (int)(sin($t * 2) * 15)), min($w, 910 + (int)(cos($t * 3) * 25)), min($h, 460 + (int)(sin($t * 2) * 15))],
                ];
            }
            $frames[] = ['t' => round($t, 2), 'boxes' => $boxes];
            $t += $stride / $fps;
        }
        $fixture = [
            'schema_version' => 1,
            'fps' => $fps,
            'source_width' => $w,
            'source_height' => $h,
            'stride' => $stride,
            'duration' => $duration,
            'frames' => $frames,
        ];
        $path = $dir . '/tracks.json';
        file_put_contents($path, json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Fixture written: $path (" . count($frames) . " keyframes, duration $duration s)");

        // Create matching DetectionEvents for the phone appearances
        $eventsCreated = 0;
        if ($videoAsset) {
            foreach ([[22.0, 'D2', 'behavior', 'pending', 'cell phone detected'], [65.0, 'D2', 'behavior', 'confirmed_suspicious', 'cell phone detected']] as $ev) {
                \App\Models\DetectionEvent::create([
                    'exam_session_id' => $videoAsset->exam_session_id ?? 1,
                    'video_asset_id' => $videoAsset->id,
                    'event_type' => $ev[1],
                    'event_category' => $ev[2],
                    'temporary_track_id' => $ev[4] === 'cell phone detected' ? 7 : 19,
                    'review_status' => $ev[3],
                    'started_at_seconds' => $ev[0],
                    'started_at_frame' => (int)($ev[0] * $fps),
                    'confidence' => 0.83,
                ]);
                $eventsCreated++;
            }
        }
        $this->info("Created $eventsCreated matching DetectionEvent rows.");
    }
}
