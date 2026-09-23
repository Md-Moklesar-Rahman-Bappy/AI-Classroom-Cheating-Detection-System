<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->hasAnyRole(['system_admin', 'auditor', 'exam_admin'])) {
            abort(403);
        }
        $logs = AuditLog::with('actor')->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }
}
