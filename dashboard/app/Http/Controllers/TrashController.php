<?php
namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\CameraSource;
use App\Models\VideoAsset;
use App\Models\AnalysisJob;
use App\Models\User;
use App\Models\ModelVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TrashController extends Controller
{
    private array $allowed = [
        'ExamRoom' => ExamRoom::class,
        'ExamSession' => ExamSession::class,
        'CameraSource' => CameraSource::class,
        'VideoAsset' => VideoAsset::class,
        'AnalysisJob' => AnalysisJob::class,
        'DetectionEvent' => DetectionEvent::class,
        'EventEvidence' => EventEvidence::class,
        'User' => User::class,
        'ModelVersion' => ModelVersion::class,
    ];

    private function assertAllowed(string $type)
    {
        if (!array_key_exists($type, $this->allowed)) abort(400, 'Invalid trash type');
        return $this->allowed[$type];
    }

    private function authRestore()
    {
        if (!auth()->user()->hasAnyRole(['system_admin','exam_admin'])) abort(403);
    }

    private function authForce()
    {
        if (!auth()->user()->hasAnyRole(['system_admin'])) abort(403);
    }

    public function index()
    {
        $this->authRestore();
        $types = array_keys($this->allowed);
        $data = [];
        foreach ($types as $label) {
            $class = $this->allowed[$label];
            $data[$label] = $class::onlyTrashed()->latest()->get();
        }
        return view('trash.index', ['trashTypes' => $types, 'groups' => $data]);
    }

    public function restore(Request $request, int $id)
    {
        $this->authRestore();
        $type = $request->query('type');
        $class = $this->assertAllowed($type);
        $record = $class::onlyTrashed()->findOrFail($id);
        AuditHelper::log(strtolower($type) . '.restore', strtolower($type), (string)$id, 'success', ['before_delete' => false, 'permanent' => false]);
        $record->restore();
        return back()->with('success', 'Record restored.');
    }

    public function bulkRestore(Request $request)
    {
        $this->authRestore();
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','distinct'],
            'type' => ['required','string','in:ExamRoom,ExamSession,CameraSource,VideoAsset,AnalysisJob,DetectionEvent,EventEvidence,User,ModelVersion'],
            'confirmation' => ['required','string','in:RESTORE SELECTED'],
        ]);
        $class = $this->assertAllowed($data['type']);
        $ids = array_map('intval', $data['ids']);
        $records = $class::onlyTrashed()->whereIn('id', $ids)->get();
        if ($records->count() === 0) {
            return redirect()->route('trash.index')->with('error', 'No trashed records found to restore.');
        }
        $restored = 0;
        DB::transaction(function () use ($records, &$restored) {
            foreach ($records as $record) {
                $record->restore();
                AuditHelper::log(strtolower($record->getTable()).'.restore', strtolower($record->getTable()), (string)$record->id, 'success', ['bulk'=>true]);
                $restored++;
            }
        });
        return redirect()->route('trash.index')->with('success', "$restored item(s) restored.");
    }

    public function forceDelete(Request $request, int $id)
    {
        $this->authForce();
        $type = $request->query('type');
        $class = $this->assertAllowed($type);
        $record = $class::onlyTrashed()->findOrFail($id);
        AuditHelper::log(strtolower($type) . '.force_delete', strtolower($type), (string)$id, 'success', ['before_delete' => true, 'permanent' => true]);
        if ($type === 'EventEvidence') {
            try {
                $path = $record->file_path;
                if ($path && Storage::exists($path)) Storage::delete($path);
            } catch (\Throwable $e) {
                // Log failure but continue
                AuditHelper::log('evidence.file_delete_failed', strtolower($type), (string)$id, 'failure', ['error' => $e->getMessage()]);
            }
        }
        $record->forceDelete();
        return back()->with('success', 'Permanently deleted.');
    }

    public function bulkForceDelete(Request $request)
    {
        $this->authForce();
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','distinct'],
            'type' => ['required','string','in:ExamRoom,ExamSession,CameraSource,VideoAsset,AnalysisJob,DetectionEvent,EventEvidence,User,ModelVersion'],
            'confirmation' => ['required','string','in:DELETE PERMANENTLY'],
        ]);
        $class = $this->assertAllowed($data['type']);
        $ids = array_map('intval', $data['ids']);
        $records = $class::onlyTrashed()->whereIn('id', $ids)->get();
        if ($records->count() === 0) {
            return redirect()->route('trash.index')->with('error', 'No trashed records found to permanently delete.');
        }
        $deleted = 0;
        DB::transaction(function () use ($records, &$deleted) {
            foreach ($records as $record) {
                if ($record->getTable() === 'event_evidence' || $record instanceof \App\Models\EventEvidence) {
                    try {
                        $path = $record->file_path;
                        if ($path && Storage::exists($path)) Storage::delete($path);
                    } catch (\Throwable $e) {
                        AuditHelper::log('evidence.file_delete_failed', strtolower($record->getTable()), (string)$record->id, 'failure', ['error'=>$e->getMessage()]);
                    }
                }
                AuditHelper::log(strtolower($record->getTable()).'.force_delete', strtolower($record->getTable()), (string)$record->id, 'success', ['bulk'=>true, 'before_delete'=>true, 'permanent'=>true]);
                $record->forceDelete();
                $deleted++;
            }
        });
        return redirect()->route('trash.index')->with('success', "$deleted item(s) permanently deleted.");
    }
}
