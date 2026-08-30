<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\EventEvidence;
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
