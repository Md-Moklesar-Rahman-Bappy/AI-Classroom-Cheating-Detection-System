<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\EventEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function show(EventEvidence $evidence)
    {
        $this->authorizeAccess($evidence);
        $path = $evidence->file_path;
        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }
        AuditHelper::log('evidence_accessed', 'event_evidence', (string) $evidence->id);
        $fullPath = Storage::disk('local')->path($path);

        return response()->file($fullPath);
    }

    public function download(Request $request, EventEvidence $evidence)
    {
        $this->authorizeAccess($evidence);
        if (! Storage::disk('local')->exists($evidence->file_path)) abort(404);
        $format = $request->query('format', 'original');
        $fullPath = Storage::disk('local')->path($evidence->file_path);
        if ($format === 'json') {
            AuditHelper::log('evidence_metadata_downloaded', 'event_evidence', (string) $evidence->id);
            return response()->json([
                'id' => $evidence->id,
                'detection_event_id' => $evidence->detection_event_id,
                'file_path' => $evidence->file_path,
                'file_type' => $evidence->file_type,
                'frame_number' => $evidence->frame_number,
                'captured_at_seconds' => $evidence->captured_at_seconds,
                'width' => $evidence->width,
                'height' => $evidence->height,
                'checksum_sha256' => $evidence->checksum_sha256,
                'created_at' => $evidence->created_at,
            ]);
        }
        if ($format === 'png' || $format === 'jpg') {
            AuditHelper::log('evidence_downloaded', 'event_evidence', (string) $evidence->id, 'success', ['format' => $format]);
            if ($format === 'png') {
                $img = @imagecreatefromstring(file_get_contents($fullPath));
                if ($img) {
                    ob_start();
                    imagepng($img);
                    $data = ob_get_clean();
                    imagedestroy($img);
                    return response($data, 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'attachment; filename="evidence_'.$evidence->id.'.png"']);
                }
            }
            $ext = $format === 'png' ? 'png' : 'jpg';
            return response()->download($fullPath, "evidence_{$evidence->id}.{$ext}", ['Content-Type' => $format === 'png' ? 'image/png' : 'image/jpeg']);
        }
        AuditHelper::log('evidence_downloaded', 'event_evidence', (string) $evidence->id, 'success', ['format' => 'original']);
        return response()->download($fullPath, basename($evidence->file_path));
    }

    public function destroy(EventEvidence $evidence)
    {
        $this->authorizeAccess($evidence);
        if (! auth()->user()->hasAnyRole(['system_admin','exam_admin'])) abort(403);
        $id = $evidence->id;
        $evidence->delete();
        AuditHelper::log('evidence_deleted', 'event_evidence', (string) $id);

        return back()->with('success', 'Evidence deleted (soft)');
    }

    public function bulkDestroy(Request $request)
    {
        if (! auth()->user()->hasAnyRole(['system_admin','exam_admin'])) abort(403);
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->withErrors(['ids'=>'No selection']);
        $count = EventEvidence::whereIn('id', $ids)->delete();
        AuditHelper::log('evidence_bulk_deleted', 'event_evidence', implode(',', $ids), 'success', ['count'=>$count]);
        return back()->with('success', "$count evidences deleted");
    }

    public function restore($id)
    {
        if (! auth()->user()->hasAnyRole(['system_admin','exam_admin'])) abort(403);
        $ev = EventEvidence::onlyTrashed()->findOrFail($id);
        $ev->restore();
        AuditHelper::log('evidence_restored', 'event_evidence', (string) $id);
        return back()->with('success', 'Evidence restored');
    }

    private function authorizeAccess(EventEvidence $evidence)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'reviewer', 'invigilator', 'auditor'])) {
            abort(403);
        }
        if (str_contains($evidence->file_path, '..')) {
            abort(403);
        }
    }
}
