<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class ExamSessionController extends Controller
{
    public function index()
    {
        $sessions = ExamSession::with('room')->paginate(10);

        return view('exam-sessions.index', compact('sessions'));
    }

    public function create()
    {
        $rooms = ExamRoom::all();

        return view('exam-sessions.create', compact('rooms'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:200', 'exam_room_id' => 'nullable|exists:exam_rooms,id', 'status' => 'required|in:pending,active,completed,cancelled']);
        $data = $request->only(['name', 'exam_room_id', 'status']);
        $data['created_by'] = auth()->id();
        $session = ExamSession::create($data);
        AuditHelper::log('session_created', 'exam_session', (string) $session->id);

        return redirect()->route('exam-sessions.index')->with('success', 'Session created');
    }

    public function show(ExamSession $examSession)
    {
        $examSession->load(['room', 'videoAssets', 'analysisJobs']);

        return view('exam-sessions.show', compact('examSession'));
    }

    public function edit(ExamSession $examSession)
    {
        $rooms = ExamRoom::all();

        return view('exam-sessions.edit', compact('examSession', 'rooms'));
    }

    public function update(Request $request, ExamSession $examSession)
    {
        $request->validate(['name' => 'required|string|max:200', 'exam_room_id' => 'nullable|exists:exam_rooms,id', 'status' => 'required|in:pending,active,completed,cancelled']);
        $examSession->update($request->only(['name', 'exam_room_id', 'status']));
        AuditHelper::log('session_updated', 'exam_session', (string) $examSession->id);

        return redirect()->route('exam-sessions.index')->with('success', 'Session updated');
    }

    public function destroy(ExamSession $examSession)
    {
        $examSession->delete();
        AuditHelper::log('session_deleted', 'exam_session', (string) $examSession->id);

        return redirect()->route('exam-sessions.index')->with('success','Session deleted');
    }
}
