<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use App\Models\DetectionEvent;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'rooms' => ExamRoom::count(),
            'sessions' => ExamSession::count(),
            'jobs' => AnalysisJob::count(),
            'events' => DetectionEvent::count(),
        ];

        return view('dashboard', compact('stats'));
    }
}
