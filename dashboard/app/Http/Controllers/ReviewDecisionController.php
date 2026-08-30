<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\DetectionEvent;
use App\Models\ReviewDecision;
use Illuminate\Http\Request;

class ReviewDecisionController extends Controller
{
    public function store(Request $request, DetectionEvent $detectionEvent)
    {
        $request->validate(['decision' => 'required|in:confirmed_suspicious,dismissed_normal,needs_further_review', 'note' => 'nullable|string|max:500']);
        if (! auth()->user()->hasAnyRole(['reviewer', 'system_admin', 'exam_admin'])) {
            abort(403);
        }
        $decision = ReviewDecision::create(['detection_event_id' => $detectionEvent->id, 'exam_session_id' => $detectionEvent->exam_session_id, 'reviewed_by' => auth()->id(), 'decision' => $request->decision, 'note' => $request->note]);
        $detectionEvent->update(['review_status' => $request->decision, 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'reviewer_note' => $request->note]);
        AuditHelper::log('event_reviewed', 'detection_event', (string) $detectionEvent->id, 'success', ['decision' => $request->decision]);

        return redirect()->route('detection-events.show', $detectionEvent)->with('success', 'Decision recorded');
    }
}
