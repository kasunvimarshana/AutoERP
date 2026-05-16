<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Services;

use Illuminate\Support\Facades\Broadcast;
use Modules\Core\Infrastructure\Broadcasting\Contracts\ChannelManagerInterface;

/**
 * Pluggable channel registry.
 *
 * Modules call {@see register()} during their service-provider boot phase to
 * declare channel authorization logic.  A single call to {@see registerAll()}
 * (from CoreServiceProvider::boot) then forwards every definition to Laravel's
 * Broadcast facade, keeping the registration lifecycle clean and centralized.
 */
final class ChannelManager implements ChannelManagerInterface
{
    /** @var array<string, callable> */
    private array $channels = [];

    /**
     * {@inheritDoc}
     */
    public function register(string $channelPattern, callable $callback): void
    {
        $this->channels[$channelPattern] = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function registerAll(): void
    {
        foreach ($this->channels as $pattern => $callback) {
            Broadcast::channel($pattern, $callback);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $channelPattern): ?callable
    {
        return $this->channels[$channelPattern] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->channels;
    }
}
