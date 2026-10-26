<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

class TrustedProxyMiddleware extends TrustProxies
{
    protected $proxies = null;

    public function proxies()
    {
        return config('trustedproxy.proxies', null);
    }
}
