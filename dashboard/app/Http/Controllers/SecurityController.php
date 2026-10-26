<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Services\LoginProtectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewSecurityCenter');

        $events = DB::table('login_security_events')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('security.index', compact('events'));
    }

    public function unblockPair(Request $request, string $email, string $ip)
    {
        $this->authorize('unblockLoginSource');

        $request->validate([
            'reason' => 'required|string|max:200',
        ]);

        $service = new LoginProtectionService($email, $ip);
        $service->unblockPair();

        AuditHelper::log('security_unblock_pair', 'login_security_event', '', 'success', ['email' => $email, 'ip' => $ip]);

        return redirect()->route('security.index')->with('status', 'Pair unblocked.');
    }

    public function unblockIp(Request $request, string $ip = '')
    {
        $this->authorize('unblockLoginSource');

        $ip = $ip ?: $request->input('ip', '');

        $request->validate([
            'reason' => 'required|string|max:200',
            'ip' => $ip ? 'nullable|ip' : 'required|ip',
        ]);

        $email = $request->input('email', '');
        $service = new LoginProtectionService($email ?: 'system', $ip);
        $service->unblockIp();

        AuditHelper::log('security_unblock_ip', 'login_security_event', '', 'success', ['email' => $email, 'ip' => $ip]);

        return redirect()->route('security.index')->with('status', 'IP unblocked.');
    }
}
