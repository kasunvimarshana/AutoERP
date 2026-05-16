<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Contracts;

/**
 * Contract for the application-wide broadcast service.
 *
 * Implementations wrap a concrete broadcaster (e.g. Laravel Reverb) and
 * expose a minimal surface so callers remain decoupled from the driver.
 */
interface BroadcastServiceInterface
{
    /**
     * Broadcast a payload to the given channel under the given event name.
     *
     * @param  string  $channel  Fully-qualified channel name (e.g. "private-tenant.1")
     * @param  string  $event  Event name surfaced to WebSocket clients
     * @param  array<string, mixed>  $data
     */
    public function broadcast(string $channel, string $event, array $data = []): void;

    /**
     * Resolve the canonical channel name for a module / resource type.
     *
     * @param  string  $type  Channel type identifier (e.g. "tenant", "org-unit", "user")
     * @param  int|string  $id  Resource identifier
     */
    public function channelName(string $type, int|string $id): string;
}
