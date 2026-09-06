<?php

use App\Models\User;
use App\Models\Role;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\AnalysisJob;
use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use App\Models\CameraSource;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'system_admin'], ['description' => 'admin']);
    $this->admin->roles()->sync([$role->id]);
});

it('can soft delete event and restore', function () {
    $room = ExamRoom::create(['name' => 'R'.uniqid(), 'capacity' => 30]);
    $session = ExamSession::create(['name' => 'S1', 'status' => 'pending', 'created_by' => $this->admin->id, 'exam_room_id' => $room->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('a',64)], ['name'=>'yolo11n.pt','version'=>'v1','weight_filename'=>'yolo11n.pt','class_list'=>json_encode(['person']),'license'=>'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id'=>$session->id,'source_type'=>'recorded_video','model_version_id'=>$model->id,'status'=>'completed','config'=>json_encode([]),'created_by'=>$this->admin->id]);
    $event = DetectionEvent::create(['exam_session_id'=>$session->id,'analysis_job_id'=>$job->id,'model_version_id'=>$model->id,'source_type'=>'recorded_video','temporary_track_id'=>4,'event_type'=>'D2','review_status'=>'pending']);
    $this->actingAs($this->admin)->delete(route('detection-events.destroy',$event))->assertRedirect();
    expect(DetectionEvent::find($event->id))->toBeNull();
    expect(DetectionEvent::onlyTrashed()->find($event->id))->not->toBeNull();
    $this->actingAs($this->admin)->post(route('detection-events.restore',$event->id))->assertRedirect();
    expect(DetectionEvent::find($event->id))->not->toBeNull();
});

it('can delete evidence', function () {
    Storage::fake('local');
    $room = ExamRoom::create(['name' => 'R'.uniqid(), 'capacity' => 30]);
    $session = ExamSession::create(['name' => 'S2', 'status' => 'pending', 'created_by' => $this->admin->id, 'exam_room_id' => $room->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('b',64)], ['name'=>'yolo11n.pt','version'=>'v2','weight_filename'=>'yolo11n.pt','class_list'=>json_encode(['person']),'license'=>'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id'=>$session->id,'source_type'=>'recorded_video','model_version_id'=>$model->id,'status'=>'completed','config'=>json_encode([]),'created_by'=>$this->admin->id]);
    $event = DetectionEvent::create(['exam_session_id'=>$session->id,'analysis_job_id'=>$job->id,'model_version_id'=>$model->id,'source_type'=>'recorded_video','temporary_track_id'=>1,'event_type'=>'B1','review_status'=>'pending']);
    Storage::disk('local')->put('evidence/test.jpg','fake');
    $ev = EventEvidence::create(['detection_event_id'=>$event->id,'file_path'=>'evidence/test.jpg','file_type'=>'snapshot','frame_number'=>5]);
    $this->actingAs($this->admin)->delete(route('evidence.destroy',$ev))->assertRedirect();
    expect(EventEvidence::find($ev->id))->toBeNull();
    $this->actingAs($this->admin)->post(route('evidence.restore',$ev->id))->assertRedirect();
});

it('can soft delete camera and prevent active delete', function () {
    $cam = CameraSource::create(['name'=>'Cam1','source_type'=>'test_source','identifier'=>'test:0','exam_session_id'=>null,'created_by'=>$this->admin->id,'status'=>'inactive']);
    $this->actingAs($this->admin)->delete(route('camera-sources.destroy',$cam))->assertRedirect();
    expect(CameraSource::find($cam->id))->toBeNull();
    $this->actingAs($this->admin)->post(route('camera-sources.restore',$cam->id))->assertRedirect();
    $cam2 = CameraSource::create(['name'=>'Cam2','source_type'=>'test_source','identifier'=>'test:1','created_by'=>$this->admin->id,'status'=>'connected']);
    $this->actingAs($this->admin)->delete(route('camera-sources.destroy',$cam2))->assertSessionHasErrors();
});

it('evidence download returns json and file', function () {
    Storage::fake('local');
    $room = ExamRoom::create(['name' => 'R'.uniqid(), 'capacity' => 30]);
    $session = ExamSession::create(['name' => 'S3', 'status' => 'pending', 'created_by' => $this->admin->id, 'exam_room_id' => $room->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('c',64)], ['name'=>'yolo11n.pt','version'=>'v3','weight_filename'=>'yolo11n.pt','class_list'=>json_encode(['person']),'license'=>'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id'=>$session->id,'source_type'=>'recorded_video','model_version_id'=>$model->id,'status'=>'completed','config'=>json_encode([]),'created_by'=>$this->admin->id]);
    $event = DetectionEvent::create(['exam_session_id'=>$session->id,'analysis_job_id'=>$job->id,'model_version_id'=>$model->id,'source_type'=>'recorded_video','temporary_track_id'=>1,'event_type'=>'B2','review_status'=>'pending']);
    Storage::disk('local')->put('evidence/test2.jpg','fakeimagecontent');
    $ev = EventEvidence::create(['detection_event_id'=>$event->id,'file_path'=>'evidence/test2.jpg','file_type'=>'snapshot','frame_number'=>10,'captured_at_seconds'=>1.5]);
    $this->actingAs($this->admin)->get(route('evidence.download',$ev).'?format=json')->assertOk()->assertJson(['id'=>$ev->id]);
});

it('bulk delete events', function () {
    $room = ExamRoom::create(['name' => 'R'.uniqid(), 'capacity' => 30]);
    $session = ExamSession::create(['name' => 'S4', 'status' => 'pending', 'created_by' => $this->admin->id, 'exam_room_id' => $room->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('d',64)], ['name'=>'yolo11n.pt','version'=>'v4','weight_filename'=>'yolo11n.pt','class_list'=>json_encode(['person']),'license'=>'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id'=>$session->id,'source_type'=>'recorded_video','model_version_id'=>$model->id,'status'=>'completed','config'=>json_encode([]),'created_by'=>$this->admin->id]);
    $e1 = DetectionEvent::create(['exam_session_id'=>$session->id,'analysis_job_id'=>$job->id,'model_version_id'=>$model->id,'source_type'=>'recorded_video','temporary_track_id'=>1,'event_type'=>'D1']);
    $e2 = DetectionEvent::create(['exam_session_id'=>$session->id,'analysis_job_id'=>$job->id,'model_version_id'=>$model->id,'source_type'=>'recorded_video','temporary_track_id'=>2,'event_type'=>'B4']);
    $this->actingAs($this->admin)->post(route('detection-events.bulk-delete'), ['ids'=>[$e1->id,$e2->id]])->assertRedirect();
    expect(DetectionEvent::count())->toBe(0);
});
