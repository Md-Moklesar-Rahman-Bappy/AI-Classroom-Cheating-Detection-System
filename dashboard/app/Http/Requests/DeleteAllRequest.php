<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class DeleteAllRequest extends FormRequest {
    public function authorize(): bool { return auth()->user()?->hasAnyRole(['system_admin','exam_admin']) ?? false; }
    public function rules(): array { return ['type'=>'required|string|in:exam_room,exam_session,camera_source,video_asset,analysis_job,detection_event,evidence,user,model_version','confirmation'=>'required|string|in:DELETE ALL']; }
}
