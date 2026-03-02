<?php

declare(strict_types=1);

namespace Modules\Core\Application\Pipes;

use Closure;
use Illuminate\Support\Facades\Log;

final class AuditLogPipe
{
    public function handle(mixed $payload, Closure $next): mixed
    {
        $before = is_object($payload) ? get_class($payload) : gettype($payload);

        $result = $next($payload);

        Log::channel('stack')->info('Command executed', [
            'command' => $before,
            'user_id' => auth()->id(),
            'ip' => request()?->ip(),
            'request_id' => request()?->header('X-Request-ID'),
        ]);

        return $result;
    }
}
