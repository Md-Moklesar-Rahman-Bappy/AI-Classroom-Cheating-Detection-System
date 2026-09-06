<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use App\Models\CameraSource;
use App\Models\DetectionEvent;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Services\AiServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'rooms' => ExamRoom::count(),
            'sessions' => ExamSession::count(),
            'jobs' => AnalysisJob::count(),
            'events' => DetectionEvent::count(),
        ];
        $aiStatus = 'unavailable';
        $aiLatency = null;
        try {
            $client = app(AiServiceClient::class);
            $start = microtime(true);
            $client->healthCheck();
            $aiLatency = (int) ((microtime(true) - $start) * 1000);
            $aiStatus = 'online';
        } catch (\Throwable $e) {
            $aiStatus = 'unavailable';
        }
        $dbStatus = 'online';
        try { DB::select('SELECT 1'); } catch (\Throwable $e) { $dbStatus = 'unavailable'; }
        $cameraCount = CameraSource::count();
        $liveCount = CameraSource::whereIn('status', ['connected', 'testing'])->count();
        $queuePending = AnalysisJob::whereIn('status', ['pending', 'queued', 'processing'])->count();

        return view('dashboard', compact('stats', 'aiStatus', 'aiLatency', 'dbStatus', 'cameraCount', 'liveCount', 'queuePending'));
    }
}
