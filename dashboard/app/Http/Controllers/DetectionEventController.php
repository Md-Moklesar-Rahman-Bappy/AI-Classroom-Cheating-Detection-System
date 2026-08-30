<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\DetectionEvent;
use Illuminate\Http\Request;

class DetectionEventController extends Controller
{
    public function index(Request $request)
    {
        $query = DetectionEvent::with(['job', 'evidences']);
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        if ($request->filled('review_status')) {
            $query->where('review_status', $request->review_status);
        }
        $events = $query->paginate(15);

        return view('detection-events.index', compact('events'));
    }

    public function show(DetectionEvent $detectionEvent)
    {
        $detectionEvent->load(['evidences', 'job']);
        AuditHelper::log('event_viewed', 'detection_event', (string) $detectionEvent->id);

        return view('detection-events.show', compact('detectionEvent'));
    }
}
