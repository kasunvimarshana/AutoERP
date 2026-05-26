<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Subscribers;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Audit\Infrastructure\Listeners\CaptureConfiguredSystemEventListener;

final class AuditEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $listener = CaptureConfiguredSystemEventListener::class;

        foreach ((array) config('audit.events.listen', []) as $eventName) {
            if (is_string($eventName) && trim($eventName) !== '') {
                $events->listen($eventName, $listener);
            }
        }

        if ((bool) config('audit.events.capture_wildcard', false)) {
            $events->listen('*', $listener);
        }
    }
}
