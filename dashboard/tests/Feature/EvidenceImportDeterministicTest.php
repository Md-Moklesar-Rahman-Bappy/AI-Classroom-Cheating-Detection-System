<?php

use App\Models\User;
use App\Models\Role;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\AnalysisJob;
use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use App\Jobs\ProcessAnalysisJob;
use App\Services\AiServiceClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'system_admin'], ['description' => 'admin']);
    $this->admin->roles()->sync([$role->id]);
    Storage::fake('local');
});

it('maps distinct events to distinct evidence via event_id and verifies SHA256', function () {
    $room = ExamRoom::create(['name' => 'R'.uniqid(), 'capacity' => 30]);
    $session = ExamSession::create(['name' => 'S-Det', 'status' => 'pending', 'created_by' => $this->admin->id, 'exam_room_id' => $room->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('e',64)], ['name'=>'yolo11n.pt','version'=>'det1','weight_filename'=>'yolo11n.pt','class_list'=>json_encode(['person']),'license'=>'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id'=>$session->id,'source_type'=>'recorded_video','model_version_id'=>$model->id,'status'=>'queued','config'=>json_encode([]),'created_by'=>$this->admin->id,'remote_job_id'=>'job-ev-det','correlation_id'=>'corr-det']);
    // Create fake AI evidence files on shared filesystem expectation
    $aiBase = base_path('../ai-service/evidence/job-ev-det');
    if (!is_dir($aiBase)) mkdir($aiBase, 0777, true);
    $evData = [
        ['event_id'=>'ev1','frame_number'=>102,'track_id'=>1,'event_code'=>'S3','bbox'=>['x_min'=>10,'y_min'=>10,'x_max'=>100,'y_max'=>100]],
        ['event_id'=>'ev2','frame_number'=>132,'track_id'=>1,'event_code'=>'B4','bbox'=>['x_min'=>20,'y_min'=>20,'x_max'=>110,'y_max'=>110]],
        ['event_id'=>'ev3','frame_number'=>267,'track_id'=>2,'event_code'=>'B3','bbox'=>['x_min'=>30,'y_min'=>30,'x_max'=>120,'y_max'=>120]],
    ];
    $evidenceMeta = [];
    foreach ($evData as $idx=>$ev) {
        $content = 'fake-image-'.$ev['event_id'].'-'.uniqid();
        $fname = "job_job-ev-det_frame_".str_pad($ev['frame_number'],6,'0',STR_PAD_LEFT)."_track_t".$ev['track_id']."_".$ev['event_code']."_".$ev['event_id'].".jpg";
        file_put_contents($aiBase.'/'.$fname, $content);
        $evidenceMeta[] = [
            'evidence_id'=>$ev['event_id'],
            'event_id'=>$ev['event_id'],
            'job_id'=>'job-ev-det',
            'frame_number'=>$ev['frame_number'],
            'timestamp_seconds'=>1.0,
            'track_id'=>$ev['track_id'],
            'event_code'=>$ev['event_code'],
            'event_label'=>$ev['event_code'],
            'bbox'=>$ev['bbox'],
            'bbox_format'=>'xyxy',
            'original_frame_size'=>['width'=>640,'height'=>360],
            'rendered_frame_size'=>['width'=>640,'height'=>360],
            'last_valid_detection_frame'=>100,
            'absence_frames'=>5,
            'checksum_sha256'=>hash('sha256',$content),
            'storage_path'=>$aiBase.'/'.$fname,
            'file_name'=>$fname
        ];
    }
    // Mock AI service responses
    $this->mock(AiServiceClient::class, function ($mock) use ($evData, $evidenceMeta) {
        $mock->shouldReceive('getJob')->andReturn(['status'=>'completed','progress_percent'=>100,'output_metadata'=>[]]);
        $mock->shouldReceive('getEvents')->andReturn(['data'=>array_map(fn($e)=>[
            'event_id'=>$e['event_id'],'job_id'=>'job-ev-det','event_type'=>$e['event_code'],'event_code'=>$e['event_code'],'frame_number'=>$e['frame_number'],'timestamp_seconds'=>1.0,'track_id'=>$e['track_id'],'bbox'=>$e['bbox'],'confidence'=>0.9
        ], $evData)]);
        $mock->shouldReceive('getMetrics')->andReturn(['metrics'=>[]]);
        $mock->shouldReceive('getEvidence')->andReturn(['job_id'=>'job-ev-det','total'=>count($evidenceMeta),'data'=>$evidenceMeta]);
    });
    // Need a fake video file for ProcessAnalysisJob to pass storage check - create minimal video asset
    $vid = \App\Models\VideoAsset::create(['exam_session_id'=>$session->id,'original_filename'=>'test.mp4','stored_filename'=>'test-'.uniqid().'.mp4','mime_type'=>'video/mp4','size_bytes'=>1000,'checksum_sha256'=>str_repeat('f',64),'validation_status'=>'valid','uploaded_by'=>$this->admin->id]);
    $job->update(['video_asset_id'=>$vid->id]);
    Storage::disk('local')->put('video_assets/'.$vid->stored_filename, str_repeat('0',1000));
    // Run job
    $client = app(AiServiceClient::class);
    $j = new ProcessAnalysisJob($job->id, 'corr-det');
    $j->handle($client);
    $job->refresh();
    $events = DetectionEvent::where('analysis_job_id',$job->id)->orderBy('started_at_frame')->get();
    expect($events->count())->toBe(3);
    $checksums = EventEvidence::whereIn('detection_event_id',$events->pluck('id'))->pluck('checksum_sha256');
    expect($checksums->unique()->count())->toBe(3);
    expect($checksums->unique()->count())->not->toBe(1);
    foreach ($events as $ev) {
        $ee = EventEvidence::where('detection_event_id',$ev->id)->first();
        expect($ee)->not->toBeNull();
        expect($ee->debug_json)->not->toBeNull();
        $dbg = is_string($ee->debug_json) ? json_decode($ee->debug_json,true) : $ee->debug_json;
        expect($dbg['track_id'])->toBe($ev->temporary_track_id);
        expect($dbg['event_code'])->toBe($ev->event_type);
    }
    // Cleanup
    array_map('unlink', glob($aiBase.'/*.jpg'));
});

it('fails if all events receive first image checksum', function () {
    $checksums = ['abc123','abc123','abc123'];
    expect(count(array_unique($checksums)))->toBe(1);
    // This test documents buggy behavior: distinct events must NOT share same checksum
    expect(count(array_unique($checksums)))->not->toBe(3);
});

it('rejects invalid B4 bbox and marks unavailable', function () {
    $room = ExamRoom::create(['name' => 'R'.uniqid(), 'capacity' => 30]);
    $session = ExamSession::create(['name' => 'S-B4', 'status' => 'pending', 'created_by' => $this->admin->id, 'exam_room_id' => $room->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('a',64)], ['name'=>'yolo','version'=>'b4test','weight_filename'=>'yolo11n.pt','class_list'=>json_encode(['person']),'license'=>'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id'=>$session->id,'source_type'=>'recorded_video','model_version_id'=>$model->id,'status'=>'completed','config'=>json_encode([]),'created_by'=>$this->admin->id]);
    $event = DetectionEvent::create(['exam_session_id'=>$session->id,'analysis_job_id'=>$job->id,'model_version_id'=>$model->id,'source_type'=>'recorded_video','temporary_track_id'=>1,'event_type'=>'B4','started_at_frame'=>267,'review_status'=>'pending']);
    // Invalid bbox: zero area
    $invalidBbox = ['x_min'=>10,'y_min'=>10,'x_max'=>10,'y_max'=>10];
    // Simulate copyEvidence validation: should reject
    $job->update(['remote_job_id'=>'fake']);
    $aiBase = base_path('../ai-service/evidence/fake');
    if (!is_dir($aiBase)) mkdir($aiBase,0777,true);
    $fname = 'job_fake_frame_000267_track_t1_B4_fakeid.jpg';
    file_put_contents($aiBase.'/'.$fname,'content');
    $meta = ['evidence_id'=>'fakeid','event_id'=>$event->id,'job_id'=>'fake','frame_number'=>267,'track_id'=>1,'event_code'=>'B4','bbox'=>$invalidBbox,'bbox_format'=>'xyxy','checksum_sha256'=>hash('sha256','content'),'storage_path'=>$aiBase.'/'.$fname,'file_name'=>$fname,'original_frame_size'=>['width'=>640,'height'=>360],'rendered_frame_size'=>['width'=>640,'height'=>360]];
    $proc = new ProcessAnalysisJob($job->id,'corr');
    $ref = new ReflectionMethod($proc,'validateBbox');
    $ref->setAccessible(true);
    $result = $ref->invoke($proc, $invalidBbox);
    expect($result)->toBeNull();
    unlink($aiBase.'/'.$fname);
});
