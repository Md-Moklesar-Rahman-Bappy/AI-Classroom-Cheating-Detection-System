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
        if ($request->filled('event_category')) {
            $map = ['detection' => ['D1','D2','D3'], 'behavior' => ['B1','B2','B3','B4','B5'], 'system' => ['S1','S2','S3']];
            $codes = $map[$request->event_category] ?? [];
            if ($codes) $query->whereIn('event_type', $codes);
        }
        if ($request->filled('track_id')) {
            $query->where('temporary_track_id', $request->track_id);
        }
        if ($request->filled('review_status')) {
            $query->where('review_status', $request->review_status);
        }
        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }
        $events = $query->paginate(15)->withQueryString();

        return view('detection-events.index', compact('events'));
    }

    public function show(DetectionEvent $detectionEvent)
    {
        $detectionEvent->load(['evidences', 'job']);
        AuditHelper::log('event_viewed', 'detection_event', (string) $detectionEvent->id);

        return view('detection-events.show', compact('detectionEvent'));
    }

    public function destroy(Request $request, DetectionEvent $detectionEvent)
    {
        if (! auth()->user()->hasAnyRole(['system_admin', 'exam_admin'])) {
            abort(403);
        }
        if ($detectionEvent->review_status === 'confirmed_suspicious' && ! $request->boolean('force')) {
            return back()->withErrors(['event' => 'Reviewed confirmed events require force flag']);
        }
        $id = $detectionEvent->id;
        $detectionEvent->delete();
        AuditHelper::log('event_deleted', 'detection_event', (string) $id, 'success', ['event_type' => $detectionEvent->event_type]);

        return redirect()->route('detection-events.index')->with('success', 'Event deleted (soft)');
    }

    public function bulkDestroy(Request $request)
    {
        if (! auth()->user()->hasAnyRole(['system_admin', 'exam_admin'])) {
            abort(403);
        }
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->withErrors(['ids' => 'No selection']);
        $count = DetectionEvent::whereIn('id', $ids)->delete();
        AuditHelper::log('event_bulk_deleted', 'detection_event', implode(',', $ids), 'success', ['count' => $count]);

        return back()->with('success', "$count events deleted");
    }

    public function restore(Request $request, $id)
    {
        if (! auth()->user()->hasAnyRole(['system_admin', 'exam_admin'])) {
            abort(403);
        }
        $ev = DetectionEvent::onlyTrashed()->findOrFail($id);
        $ev->restore();
        AuditHelper::log('event_restored', 'detection_event', (string) $id);

        return back()->with('success', 'Event restored');
    }

    public function trashed()
    {
        $events = DetectionEvent::onlyTrashed()->with(['job'])->paginate(15);
        return view('detection-events.index', compact('events'));
    }
}
