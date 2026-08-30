<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\AnalysisJob;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function show(AnalysisJob $analysisJob)
    {
        $this->authorizeReport($analysisJob);
        $analysisJob->load(['session.room', 'videoAsset', 'modelVersion', 'events.evidences', 'metrics', 'session']);
        AuditHelper::log('report_viewed', 'analysis_job', (string) $analysisJob->id, 'success');

        return view('reports.show', compact('analysisJob'));
    }

    public function download(AnalysisJob $analysisJob)
    {
        $this->authorizeReport($analysisJob);
        $analysisJob->load(['session', 'videoAsset', 'modelVersion', 'events', 'metrics']);
        AuditHelper::log('report_downloaded', 'analysis_job', (string) $analysisJob->id, 'success');
        // For Phase 6, generate simple PDF via HTML, or return view as PDF
        $html = view('reports.pdf', compact('analysisJob'))->render();
        // If DomPDF not installed, fallback to HTML download
        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadHTML($html);

            return $pdf->download('report-'.$analysisJob->id.'.pdf');
        }

        return response($html)->header('Content-Type', 'text/html')->header('Content-Disposition', 'attachment; filename=report-'.$analysisJob->id.'.html');
    }

    private function authorizeReport(AnalysisJob $job)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'reviewer', 'auditor'])) {
            abort(403);
        }
    }
}
