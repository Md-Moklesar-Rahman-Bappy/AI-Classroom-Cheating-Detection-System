<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\AuditHelper;

class BulkResourceActionService
{
    protected array $registry = [
        'exam_room' => ['model'=>'App\Models\ExamRoom','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
        'exam_session' => ['model'=>'App\Models\ExamSession','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
        'camera_source' => ['model'=>'App\Models\CameraSource','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
        'video_asset' => ['model'=>'App\Models\VideoAsset','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
        'analysis_job' => ['model'=>'App\Models\AnalysisJob','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
        'detection_event' => ['model'=>'App\Models\DetectionEvent','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
        'evidence' => ['model'=>'App\Models\EventEvidence','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
        'user' => ['model'=>'App\Models\User','soft'=>true,'restore'=>true,'force'=>false,'auth'=>['system_admin']], // final admin protected elsewhere
        'model_version' => ['model'=>'App\Models\ModelVersion','soft'=>true,'restore'=>true,'force'=>true,'auth'=>['system_admin','exam_admin']],
    ];

    public function registry(): array { return $this->registry; }

    public function allowed(string $type): ?array
    {
        return $this->registry[$type] ?? null;
    }

    public function softDelete(string $type, array $ids, array $filters = []): array
    {
        $entry = $this->allowed($type);
        if (!$entry) return ['status'=>'fail','message'=>'Unknown resource type'];
        $model = $entry['model'];
        $query = $model::query();
        if ($filters) {
            foreach ($filters as $k => $v) {
                if (is_string($k) && $v !== '' && $v !== null) $query->where($k, $v);
            }
        }
        if (!empty($ids)) {
            $query = $query->whereIn('id', $ids);
        }
        $records = $query->get();
        if ($records->isEmpty()) return ['status'=>'fail','message'=>'No matching records'];
        $count = 0;
        DB::transaction(function () use ($records, &$count) {
            foreach ($records as $r) {
                $r->delete();
                $count++;
            }
        });
        AuditHelper::log('bulk_soft_delete', $type, implode(',', $records->pluck('id')->toArray()), 'success', ['count'=>$count]);
        return ['status'=>'ok','count'=>$count,'ids'=>$records->pluck('id')->toArray()];
    }

    public function restore(string $type, array $ids, array $filters = []): array
    {
        $entry = $this->allowed($type);
        if (!$entry || !$entry['restore']) return ['status'=>'fail','message'=>'Not allowed'];
        $model = $entry['model'];
        $query = $model::onlyTrashed();
        if ($filters) foreach ($filters as $k=>$v) if ($v!=='') $query->where($k,$v);
        if (!empty($ids)) $query = $query->whereIn('id', $ids);
        $records = $query->get();
        if ($records->isEmpty()) return ['status'=>'fail','message'=>'No trash records'];
        $count = 0;
        DB::transaction(function () use ($records, &$count) {
            foreach ($records as $r) { $r->restore(); $count++; }
        });
        AuditHelper::log('bulk_restore', $type, implode(',', $records->pluck('id')->toArray()), 'success', ['count'=>$count]);
        return ['status'=>'ok','count'=>$count];
    }

    public function forceDelete(string $type, array $ids, array $filters = []): array
    {
        $entry = $this->allowed($type);
        if (!$entry || !$entry['force']) return ['status'=>'fail','message'=>'Not allowed'];
        $model = $entry['model'];
        $query = $model::onlyTrashed();
        if ($filters) foreach ($filters as $k=>$v) if ($v!=='') $query->where($k,$v);
        if (!empty($ids)) $query = $query->whereIn('id', $ids);
        $records = $query->get();
        if ($records->isEmpty()) return ['status'=>'fail','message'=>'No trash records'];
        $count = 0;
        DB::transaction(function () use ($records, &$count) {
            foreach ($records as $r) {
                // file cleanup handled per-resource where needed
                $r->forceDelete();
                $count++;
            }
        });
        AuditHelper::log('bulk_force_delete', $type, implode(',', $records->pluck('id')->toArray()), 'success', ['count'=>$count]);
        return ['status'=>'ok','count'=>$count];
    }
}
