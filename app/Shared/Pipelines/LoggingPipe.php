<?php

declare(strict_types=1);

namespace App\Shared\Pipelines;

use Closure;
use Illuminate\Support\Facades\Log;

final class LoggingPipe
{
    public function handle(mixed $payload, Closure $next): mixed
    {
        Log::debug('Pipeline stage', [
            'pipe' => self::class,
            'payload' => is_object($payload) ? get_class($payload) : gettype($payload),
        ]);

        return $next($payload);
    }
}
