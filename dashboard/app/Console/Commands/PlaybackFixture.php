<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VideoAsset;
use Illuminate\Support\Str;

class PlaybackFixture extends Command
{
    protected $signature = 'playback:fixture {videoAsset?}';
    protected $description = 'Generate a realistic playback overlay fixture JSON';

    public function handle()
    {
        $asset = $this->argument('videoAsset');
        $videoAssetId = $asset ? (int)$asset : 1;
        $videoAsset = VideoAsset::find($videoAssetId);
        $dir = storage_path("app/playback/{$videoAssetId}");
        @mkdir($dir, 0755, true);

        $duration = 120.0;
        $fps = 25;
        $stride = 5;
        $w = 1920; $h = 1080;
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
                    'conf' => 0.82 + (sin($t * 0.4 + $tid) * 0.08),
                    'xyxy' => [max(0, $cx - 90), max(0, $cy - 180), min($w, $cx + 90), min($h, $cy + 180)],
                ];
            }
            // Phone appearances lasting 3-8s
            if ($t >= 22.0 && $t <= 28.5) {
                $boxes[] = [
                    'id' => 7,
                    'cls' => 'cell phone',
                    'conf' => 0.78 + (sin($t * 2) * 0.05),
                    'xyxy' => [455 + (int)(sin($t * 3) * 30), 300 + (int)(cos($t * 2) * 10), 500 + (int)(sin($t * 3) * 30), 350 + (int)(cos($t * 2) * 10)],
                ];
            }
            if ($t >= 65.0 && $t <= 71.2) {
                $boxes[] = [
                    'id' => 19,
                    'cls' => 'cell phone',
                    'conf' => 0.81 + (sin($t * 1.8) * 0.04),
                    'xyxy' => [860 + (int)(cos($t * 3) * 25), 410 + (int)(sin($t * 2) * 15), 910 + (int)(cos($t * 3) * 25), 460 + (int)(sin($t * 2) * 15)],
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
