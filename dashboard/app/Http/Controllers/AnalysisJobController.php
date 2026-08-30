<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\AnalysisJob;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use Illuminate\Http\Request;

class AnalysisJobController extends Controller
{
    public function index()
    {
        $jobs = AnalysisJob::with(['session', 'modelVersion'])->paginate(10);

        return view('analysis-jobs.index', compact('jobs'));
    }

    public function create()
    {
        $sessions = ExamSession::all();
        $models = ModelVersion::all();

        return view('analysis-jobs.create', compact('sessions', 'models'));
    }

    public function store(Request $request)
    {
        $request->validate(['exam_session_id' => 'required|exists:exam_sessions,id', 'source_type' => 'required|in:recorded_video,live_stream,webcam,test_source', 'model_version_id' => 'required|exists:model_versions,id']);
        $job = AnalysisJob::create(['exam_session_id' => $request->exam_session_id, 'source_type' => $request->source_type, 'model_version_id' => $request->model_version_id, 'status' => 'pending', 'config' => ['width' => 640, 'height' => 360, 'process_every_n_frames' => 3], 'created_by' => auth()->id()]);
        AuditHelper::log('job_created', 'analysis_job', (string) $job->id);

        return redirect()->route('analysis-jobs.index')->with('success', 'Job created');
    }

    public function show(AnalysisJob $analysisJob)
    {
        $analysisJob->load(['session', 'events']);

        return view('analysis-jobs.show', compact('analysisJob'));
    }

    public function destroy(AnalysisJob $analysisJob)
    {
        $analysisJob->delete();
        AuditHelper::log('job_deleted', 'analysis_job', (string) $analysisJob->id);

        return redirect()->route('analysis-jobs.index')->with('success', 'Deleted');
    }
}
