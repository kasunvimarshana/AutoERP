<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Contracts;

/**
 * Contract for the channel registry / manager.
 *
 * Implementations allow other modules to register channel authorization
 * callbacks at boot time without coupling to a specific broadcaster.
 */
interface ChannelManagerInterface
{
    /**
     * Register an authorization callback for the given channel pattern.
     *
     * @param  string  $channelPattern  Pattern as understood by Broadcast::channel()
     *                                  (e.g. "tenant.{tenantId}")
     * @param  callable  $callback  Receives ($user, ...$wildcards) and returns bool|array
     */
    public function register(string $channelPattern, callable $callback): void;

    /**
     * Register all previously stored channel definitions with the broadcaster.
     *
     * Intended to be called once during application boot.
     */
    public function registerAll(): void;

    /**
     * Resolve the authorization callback for the given channel pattern.
     */
    public function resolve(string $channelPattern): ?callable;

    /**
     * Return all registered channel patterns.
     *
     * @return array<string, callable>
     */
    public function all(): array;
}
