<?php

namespace App\Http\Controllers;

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

        return redirect()->route('security.index')->with('status', 'Pair unblocked.');
    }

    public function unblockIp(Request $request, string $ip)
    {
        $this->authorize('unblockLoginSource');

        $request->validate([
            'reason' => 'required|string|max:200',
        ]);

        $email = $request->input('email', '');
        $service = new LoginProtectionService($email ?: 'system', $ip);
        $service->unblockIp();

        return redirect()->route('security.index')->with('status', 'IP unblocked.');
    }
}
