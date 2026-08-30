<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditHelper
{
    public static function log(string $action, ?string $targetType = null, ?string $targetId = null, string $result = 'success', array $metadata = []): void
    {
        try {
            AuditLog::create([
                'actor_id' => auth()->id(),
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'ip_address' => Request::ip(),
                'user_agent' => substr(Request::userAgent() ?? '', 0, 255),
                'correlation_id' => request()->header('X-Correlation-Id'),
                'metadata' => empty($metadata) ? null : $metadata,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
        }
    }
}
