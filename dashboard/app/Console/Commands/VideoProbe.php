<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VideoAsset;
use Illuminate\Support\Facades\Storage;

class VideoProbe extends Command
{
    protected $signature = 'video:probe {videoAsset?}';
    protected $description = 'Probe video metadata via ffprobe and update VideoAsset';

    public function handle()
    {
        $ffprobe = null;
        $candidates = ['/usr/bin/ffprobe', '/usr/local/bin/ffprobe', 'C:/ffmpeg/bin/ffprobe.exe', 'ffprobe'];
        foreach ($candidates as $path) {
            if (file_exists($path) && is_executable($path) || (stripos(PHP_OS, 'WIN') === 0 && file_exists($path))) {
                $ffprobe = $path;
                break;
            }
        }
        // Try PATH
        exec('which ffprobe 2>/dev/null', $out, $code);
        if ($code === 0 && !empty($out[0])) $ffprobe = trim($out[0]);
        if (!$ffprobe) {
            $this->error('ffprobe not found. Please install FFmpeg and add it to PATH.');
            return 1;
        }
        $this->info('Using ffprobe: ' . $ffprobe);

        $query = $this->argument('videoAsset') ? VideoAsset::where('id', (int)$this->argument('videoAsset')) : VideoAsset::whereNull('width')->orWhereNull('height')->orWhereNull('fps')->orWhereNull('duration');
        $assets = $query->get();
        $updated = 0;
        foreach ($assets as $asset) {
            try {
                $storedPath = storage_path('app/' . $asset->stored_path);
                if (!file_exists($storedPath)) {
                    $this->warn("Missing file for asset {$asset->id}: {$asset->stored_filename}");
                    continue;
                }
                $cmd = escapeshellarg($ffprobe) . ' -v quiet -print_format json -show_format -show_streams ' . escapeshellarg($storedPath);
                exec($cmd . ' 2>/dev/null', $lines, $exit);
                $json = implode("\n", $lines);
                $data = json_decode($json, true);
                $stream = null;
                $videoStream = null;
                if (!empty($data['streams'])) {
                    foreach ($data['streams'] as $s) {
                        if (($s['codec_type'] ?? '') === 'video') $videoStream = $s;
                    }
                }
                $format = $data['format'] ?? [];
                $w = $videoStream ? (int)($videoStream['width'] ?? $format['width'] ?? 0) : 0;
                $h = $videoStream ? (int)($videoStream['height'] ?? $format['height'] ?? 0) : 0;
                $fpsRaw = $videoStream ? ($videoStream['r_frame_rate'] ?? $format['nb_frames'] ?? null) : null;
                $fps = 25.0;
                if ($fpsRaw && is_string($fpsRaw) && str_contains($fpsRaw, '/')) {
                    $parts = explode('/', $fpsRaw);
                    $fps = count($parts) === 2 && (int)$parts[1] > 0 ? (float)$parts[0] / (float)$parts[1] : 25.0;
                } elseif ($fpsRaw && is_numeric($fpsRaw)) {
                    $fps = (float)$fpsRaw;
                }
                $duration = isset($format['duration']) ? (float)$format['duration'] : 0.0;
                $codec = isset($videoStream['codec_name']) ? $videoStream['codec_name'] : ($format['format_name'] ?? null);

                $checksum = $asset->checksum_sha256;
                if (empty($checksum)) {
                    $checksum = hash_file('sha256', $storedPath);
                }
                $asset->update([
                    'width' => $w > 0 ? $w : null,
                    'height' => $h > 0 ? $h : null,
                    'fps' => $fps > 0 ? $fps : null,
                    'duration' => $duration > 0 ? $duration : null,
                    'codec' => $codec ? $codec : null,
                    'checksum_sha256' => $checksum,
                ]);
                $this->line("Updated asset {$asset->id}: {$w}x{$h}, fps={$fps}, duration={$duration}s, codec=" . ($codec ?: 'n/a'));
                $updated++;
            } catch (\Throwable $e) {
                $this->warn("Failed asset {$asset->id}: " . $e->getMessage());
                \App\Helpers\AuditHelper::log('video_probe.failure', 'video_asset', (string)$asset->id, 'failure', ['error' => $e->getMessage()]);
            }
        }
        $this->info("Updated $updated asset(s).");
        return 0;
    }
}
