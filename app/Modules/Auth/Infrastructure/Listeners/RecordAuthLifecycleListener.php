<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;

final class RecordAuthLifecycleListener
{
    public function handle(mixed $event = null): void
    {
        Log::debug('Auth lifecycle event processed.', [
            'event' => is_object($event) ? $event::class : gettype($event),
        ]);
    }
}
