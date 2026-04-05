<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Services;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\Dispatcher;
use Modules\Core\Infrastructure\Broadcasting\Contracts\EventBroadcasterInterface;

/**
 * Dispatches broadcastable events via the framework's event dispatcher.
 *
 * The event dispatcher handles queuing and transport through the configured
 * broadcaster (e.g. Reverb), so this service acts as a thin adapter.
 */
final class EventBroadcaster implements EventBroadcasterInterface
{
    public function __construct(private readonly Dispatcher $events) {}

    /**
     * {@inheritDoc}
     */
    public function dispatch(ShouldBroadcast $event): void
    {
        $this->events->dispatch($event);
    }
}
