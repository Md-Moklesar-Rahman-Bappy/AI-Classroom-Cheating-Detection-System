<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\ExamRoom;
use Illuminate\Http\Request;

class ExamRoomController extends Controller
{
    public function index()
    {
        $rooms = ExamRoom::paginate(10);

        return view('exam-rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('exam-rooms.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150|unique:exam_rooms', 'building' => 'nullable|string|max:150', 'capacity' => 'nullable|integer|min:1', 'camera_position_notes' => 'nullable|string']);
        $room = ExamRoom::create($request->only(['name', 'building', 'capacity', 'camera_position_notes']));
        AuditHelper::log('room_created', 'exam_room', (string) $room->id, 'success', ['name' => $room->name]);

        return redirect()->route('exam-rooms.index')->with('success', 'Room created');
    }

    public function show(ExamRoom $examRoom)
    {
        return view('exam-rooms.show', compact('examRoom'));
    }

    public function edit(ExamRoom $examRoom)
    {
        return view('exam-rooms.edit', compact('examRoom'));
    }

    public function update(Request $request, ExamRoom $examRoom)
    {
        $request->validate(['name' => 'required|string|max:150|unique:exam_rooms,name,'.$examRoom->id, 'building' => 'nullable|string|max:150', 'capacity' => 'nullable|integer|min:1']);
        $examRoom->update($request->only(['name', 'building', 'capacity', 'camera_position_notes']));
        AuditHelper::log('room_updated', 'exam_room', (string) $examRoom->id);

        return redirect()->route('exam-rooms.index')->with('success', 'Room updated');
    }

    public function destroy(ExamRoom $examRoom)
    {
        if ($examRoom->sessions()->exists()) {
            return back()->withErrors(['name' => 'Cannot delete room with sessions']);
        }
        $examRoom->delete();
        AuditHelper::log('room_deleted', 'exam_room', (string) $examRoom->id);

        return redirect()->route('exam-rooms.index')->with('success', 'Room deleted');
    }
}
