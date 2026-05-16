<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Contracts;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Contract for dispatching broadcastable events.
 *
 * Wraps the framework's event dispatcher so that callers depend only on
 * this interface and are not tightly coupled to Illuminate internals.
 */
interface EventBroadcasterInterface
{
    /**
     * Dispatch a broadcastable event.
     */
    public function dispatch(ShouldBroadcast $event): void;
}
