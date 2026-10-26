<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoginProtectionService
{
    protected string $email;
    protected string $ip;

    public function __construct(string $email, string $ip)
    {
        $this->email = Str::lower(Str::ascii($email));
        $this->ip = $this->normalizeIp($ip);
    }

    protected function normalizeIp(string $ip): string
    {
        // Basic IPv4/IPv6 normalization
        $ip = trim($ip);
        if (str_contains($ip, ':')) {
            return inet_ntop(inet_pton($ip)) ?: $ip;
        }
        return $ip;
    }

    protected function trustedClientIp(): string
    {
        $request = request();
        $trustedProxies = config('trustedproxy.proxies', []);
        if (! empty($trustedProxies) && $request->hasHeader('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            $client = trim($ips[0]);
            return $client;
        }
        return $request->ip() ?? 'unknown';
    }

    protected function pairKey(): string
    {
        return 'login_pair:'.hash('sha256', $this->email.'|'.$this->trustedClientIp());
    }

    protected function ipKey(): string
    {
        return 'login_ip:'.hash('sha256', $this->trustedClientIp());
    }

    public function isPairBlocked(): bool
    {
        $key = $this->pairKey();
        return Cache::has($key) && Cache::get($key) > now()->timestamp;
    }

    public function getPairBlockRemaining(): int
    {
        $key = $this->pairKey();
        $blockedUntil = Cache::get($key);
        if ($blockedUntil) {
            return max(0, $blockedUntil - now()->timestamp);
        }
        return 0;
    }

    public function isIpBlocked(): bool
    {
        $key = $this->ipKey();
        return Cache::has($key) && Cache::get($key) > now()->timestamp;
    }

    public function getIpBlockRemaining(): int
    {
        $key = $this->ipKey();
        $blockedUntil = Cache::get($key);
        if ($blockedUntil) {
            return max(0, $blockedUntil - now()->timestamp);
        }
        return 0;
    }

    public function recordFailedAttempt(): void
    {
        $pairKey = $this->pairKey();
        $ipKey = $this->ipKey();
        $pairMax = config('login_protection.pair_max_attempts', 3);
        $pairBlock = config('login_protection.pair_block_seconds', 86400);
        $ipWindow = config('login_protection.ip_window_seconds', 900);
        $ipMax = config('login_protection.ip_max_failures', 10);
        $ipBlock = config('login_protection.ip_block_seconds', 3600);

        // Pair tracking
        $pairData = Cache::get($pairKey, []);
        if (! is_array($pairData)) {
            $pairData = ['count' => 0, 'first' => now()->timestamp];
        }
        $pairData['count'] += 1;
        $pairData['last'] = now()->timestamp;
        Cache::put($pairKey, $pairData, $pairBlock);

        if ($pairData['count'] >= $pairMax) {
            Cache::put($pairKey, now()->addSeconds($pairBlock)->timestamp, $pairBlock);
        }

        // IP abuse tracking
        $ipData = Cache::get($ipKey, []);
        if (! is_array($ipData)) {
            $ipData = ['count' => 0, 'first' => now()->timestamp];
        }
        $ipData['count'] += 1;
        $ipData['last'] = now()->timestamp;
        Cache::put($ipKey, $ipData, $ipWindow);

        if ($ipData['count'] >= $ipMax) {
            Cache::put($ipKey, now()->addSeconds($ipBlock)->timestamp, $ipBlock);
        }

        // Safe event recording (no passwords)
        DB::table('login_security_events')->insert([
            'normalized_email' => $this->email,
            'normalized_ip' => $this->trustedClientIp(),
            'event_type' => 'failed_attempt',
            'attempt_count' => $pairData['count'],
            'first_failure' => $pairData['first'] ? now()->createFromTimestamp($pairData['first']) : now(),
            'last_failure' => now(),
            'user_agent_hash' => hash('sha256', request()->userAgent() ?? ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function clearPairCounter(): void
    {
        Cache::forget($this->pairKey());
    }

    public function unblockPair(): void
    {
        $key = $this->pairKey();
        Cache::forget($key);
        DB::table('login_security_events')->updateOrInsert(
            ['normalized_email' => $this->email, 'normalized_ip' => $this->trustedClientIp(), 'event_type' => 'unblock'],
            [
                'resolution' => 'manual_unblock',
                'updated_at' => now(),
            ]
        );
    }

    public function unblockIp(): void
    {
        $key = $this->ipKey();
        Cache::forget($key);
    }
}
