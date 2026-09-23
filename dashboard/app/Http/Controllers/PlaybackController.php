<?php
namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\VideoAsset;
use App\Models\DetectionEvent;
use App\Policies\VideoAssetPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PlaybackController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(VideoAsset::class, 'videoAsset');
    }

    public function show(VideoAsset $videoAsset)
    {
        $this->authorize('view', $videoAsset);
        AuditHelper::log('playback.view', 'video_asset', (string)$videoAsset->id, 'success');
        $events = DetectionEvent::where('video_asset_id', $videoAsset->id)
            ->orderBy('started_at_seconds')
            ->get(['id', 'event_type', 'temporary_track_id', 'started_at_seconds', 'review_status']);
        $fixturePath = storage_path("app/playback/{$videoAsset->id}/tracks.json");
        $hasOverlay = file_exists($fixturePath);
        return view('playback.show', compact('videoAsset', 'events', 'hasOverlay'));
    }

    public function tracks(VideoAsset $videoAsset)
    {
        $this->authorize('view', $videoAsset);
        $fixturePath = storage_path("app/playback/{$videoAsset->id}/tracks.json");
        if (!file_exists($fixturePath)) {
            return response()->json(['error' => 'Overlay data unavailable for this asset.'], 404);
        }
        $content = file_get_contents($fixturePath);
        return response($content, 200)
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'public, max-age=60')
            ->header('ETag', md5($content));
    }

    public function stream(VideoAsset $videoAsset, Request $request)
    {
        $this->authorize('view', $videoAsset);
        $fullPath = storage_path('app/' . $videoAsset->stored_path);
        if (!file_exists($fullPath)) abort(404);
        $fileSize = filesize($fullPath);
        $range = $request->header('Range');
        $mime = 'video/mp4';
        if ($range) {
            $ranges = [0 => ['start' => 0, 'end' => null]];
            $rangeHeader = substr($range, 6);
            $rangesRaw = explode(',', $rangeHeader);
            $rangesParsed = [];
            foreach ($rangesRaw as $rangeStr) {
                $parts = explode('-', $rangeStr);
                $start = (int)trim($parts[0]);
                $end = isset($parts[1]) && $parts[1] !== '' ? (int)trim($parts[1]) : $fileSize - 1;
                $rangesParsed[$start] = ['start' => $start, 'end' => $end];
            }
            $start = (int)array_keys($rangesParsed)[0];
            $end = $rangesParsed[$start]['end'];
            $start = max(0, $start);
            $end = min($fileSize - 1, $end);
            $length = $end - $start + 1;
            $f = fopen($fullPath, 'rb');
            fseek($f, $start);
            $data = fread($f, $length);
            fclose($f);
            return response($data, 206)
                ->header('Content-Type', $mime)
                ->header('Accept-Ranges', 'bytes')
                ->header('Content-Range', "bytes $start-$end/$fileSize")
                ->header('Content-Length', $length)
                ->header('Cache-Control', 'public, max-age=300');
        }
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
        ]);
    }
}
