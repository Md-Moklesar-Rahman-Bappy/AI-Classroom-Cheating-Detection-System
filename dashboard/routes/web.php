<?php

use App\Http\Controllers\AnalysisJobController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CameraSourceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetectionEventController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\ExamRoomController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\ModelVersionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewDecisionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoAssetController;
use App\Models\ProcessingMetric;
use App\Services\AiServiceClient;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/health/ai', function (AiServiceClient $client) {
    try {
        $data = $client->healthCheck();

        return response()->json(['ai_service' => 'ok', 'data' => $data]);
    } catch (Throwable $e) {
        return response()->json(['ai_service' => 'unavailable', 'error' => substr($e->getMessage(), 0, 200)], 503);
    }
})->name('health.ai');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('exam-rooms', ExamRoomController::class);
    Route::resource('exam-sessions', ExamSessionController::class);
    Route::resource('camera-sources', CameraSourceController::class);
    Route::resource('video-assets', VideoAssetController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('analysis-jobs', AnalysisJobController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('analysis-jobs/{analysisJob}/sync', [AnalysisJobController::class, 'sync'])->name('analysis-jobs.sync');
    Route::post('analysis-jobs/{analysisJob}/cancel', [AnalysisJobController::class, 'cancel'])->name('analysis-jobs.cancel');
    Route::post('analysis-jobs/{analysisJob}/retry', [AnalysisJobController::class, 'retry'])->name('analysis-jobs.retry');
    Route::get('analysis-jobs/{analysisJob}/report', [ReportController::class, 'show'])->name('reports.show');
    Route::get('analysis-jobs/{analysisJob}/report/download', [ReportController::class, 'download'])->name('reports.download');
    Route::resource('detection-events', DetectionEventController::class)->only(['index', 'show']);
    Route::post('detection-events/{detectionEvent}/review', [ReviewDecisionController::class, 'store'])->name('detection-events.review');
    Route::get('evidence/{evidence}', [EvidenceController::class, 'show'])->name('evidence.show')->middleware('role:system_admin,exam_admin,reviewer,invigilator,auditor');
    Route::resource('model-versions', ModelVersionController::class);
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')->middleware('role:system_admin,auditor,exam_admin');
    Route::resource('users', UserController::class)->middleware('role:system_admin');
    Route::get('live', [LiveController::class, 'index'])->name('live.index')->middleware('role:system_admin,exam_admin,invigilator,reviewer,auditor');
    Route::post('live/start', [LiveController::class, 'start'])->name('live.start')->middleware('role:system_admin,exam_admin,invigilator');
    Route::get('live/{sessionId}', [LiveController::class, 'show'])->name('live.show')->middleware('role:system_admin,exam_admin,invigilator,reviewer,auditor');
    Route::post('live/{sessionId}/stop', [LiveController::class, 'stop'])->name('live.stop')->middleware('role:system_admin,exam_admin,invigilator');
    Route::get('live/{sessionId}/health', [LiveController::class, 'health'])->name('live.health')->middleware('role:system_admin,exam_admin,invigilator,reviewer,auditor');
    Route::get('live/{sessionId}/events', [LiveController::class, 'events'])->name('live.events')->middleware('role:system_admin,exam_admin,invigilator,reviewer,auditor');
    Route::get('live/{sessionId}/preview', [LiveController::class, 'preview'])->name('live.preview')->middleware('role:system_admin,exam_admin,invigilator,reviewer,auditor');
    Route::get('settings', function () {
        return view('settings.index');
    })->name('settings.index');
    Route::get('help', function () {
        return view('help.index');
    })->name('help.index');
    Route::get('metrics', function () {
        $metrics = ProcessingMetric::with('analysisJob')->paginate(10);

        return view('metrics.index', compact('metrics'));
    })->name('metrics.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
