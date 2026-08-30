<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\CameraSource;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CameraSourceController extends Controller
{
    public function index()
    {
        $sources = CameraSource::with('session')->paginate(10);

        return view('camera-sources.index', compact('sources'));
    }

    public function create()
    {
        $sessions = ExamSession::all();

        return view('camera-sources.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:200', 'exam_session_id' => 'nullable|exists:exam_sessions,id', 'source_type' => 'required|in:webcam,rtsp,http,video_file,test_source', 'identifier' => 'required|string|max:500', 'credentials' => 'nullable|string']);
        $data = $request->only(['name', 'exam_session_id', 'source_type', 'identifier']);
        $data['created_by'] = auth()->id();
        if ($request->filled('credentials')) {
            $data['credentials_encrypted'] = Crypt::encryptString($request->input('credentials'));
        }
        $source = CameraSource::create($data);
        AuditHelper::log('camera_created', 'camera_source', (string) $source->id);

        return redirect()->route('camera-sources.index')->with('success', 'Camera source created');
    }

    public function show(CameraSource $cameraSource)
    {
        return view('camera-sources.show', compact('cameraSource'));
    }

    public function edit(CameraSource $cameraSource)
    {
        $sessions = ExamSession::all();

        return view('camera-sources.edit', compact('cameraSource', 'sessions'));
    }

    public function update(Request $request, CameraSource $cameraSource)
    {
        $request->validate(['name' => 'required|string|max:200', 'source_type' => 'required|in:webcam,rtsp,http,video_file,test_source', 'identifier' => 'required|string|max:500']);
        $data = $request->only(['name', 'source_type', 'identifier']);
        if ($request->filled('credentials')) {
            $data['credentials_encrypted'] = Crypt::encryptString($request->input('credentials'));
        }
        $cameraSource->update($data);
        AuditHelper::log('camera_updated', 'camera_source', (string) $cameraSource->id);

        return redirect()->route('camera-sources.index')->with('success', 'Camera updated');
    }

    public function destroy(CameraSource $cameraSource)
    {
        $cameraSource->delete();
        AuditHelper::log('camera_deleted', 'camera_source', (string) $cameraSource->id);

        return redirect()->route('camera-sources.index')->with('success','Deleted');
    }
}
