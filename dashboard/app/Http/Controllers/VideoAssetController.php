<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\ExamSession;
use App\Models\VideoAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoAssetController extends Controller
{
    public function index()
    {
        $assets = VideoAsset::with('session')->paginate(10);

        return view('video-assets.index', compact('assets'));
    }

    public function create()
    {
        $sessions = ExamSession::all();

        return view('video-assets.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $request->validate(['exam_session_id' => 'required|exists:exam_sessions,id', 'video' => 'required|file|mimes:mp4,avi,mov,mkv|max:512000']);
        $file = $request->file('video');
        $stored = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('video_assets', $stored, 'local');
        $asset = VideoAsset::create([
            'exam_session_id' => $request->exam_session_id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $stored,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'validation_status' => 'valid',
            'uploaded_by' => auth()->id(),
        ]);
        AuditHelper::log('video_uploaded', 'video_asset', (string) $asset->id);

        return redirect()->route('video-assets.index')->with('success', 'Video uploaded');
    }

    public function show(VideoAsset $videoAsset)
    {
        return view('video-assets.show', compact('videoAsset'));
    }

    public function destroy(VideoAsset $videoAsset)
    {
        \Storage::disk('local')->delete('video_assets/'.$videoAsset->stored_filename);
        $videoAsset->delete();
        AuditHelper::log('video_deleted', 'video_asset', (string) $videoAsset->id);

        return redirect()->route('video-assets.index')->with('success', 'Deleted');
    }
}
