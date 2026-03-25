<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Broadcasting\Services;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Modules\Core\Infrastructure\Broadcasting\Contracts\BroadcastServiceInterface;

/**
 * Default broadcast service backed by Laravel's BroadcastManager.
 *
 * Uses the application's configured broadcaster (Reverb, Pusher, null, …)
 * so the rest of the application stays decoupled from the transport layer.
 */
final class BroadcastService implements BroadcastServiceInterface
{
    /**
     * Channel-name templates keyed by type identifier.
     *
     * @var array<string, string>
     */
    private array $channelTemplates = [
        'tenant'   => 'private-tenant.{id}',
        'org-unit' => 'private-org.{id}',
        'user'     => 'private-user.{id}',
    ];

    public function __construct(private readonly BroadcastingFactory $broadcaster) {}

    /**
     * {@inheritDoc}
     */
    public function broadcast(string $channel, string $event, array $data = []): void
    {
        $this->broadcaster->connection()->broadcast([$channel], $event, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function channelName(string $type, int|string $id): string
    {
        $template = $this->channelTemplates[$type]
            ?? 'private-'.$type.'.{id}';

        return str_replace('{id}', (string) $id, $template);
    }

    /**
     * Register a custom channel-name template for a given type.
     *
     * Allows modules to override the default naming convention at boot time.
     */
    public function registerTemplate(string $type, string $template): void
    {
        $this->channelTemplates[$type] = $template;
    }
}
