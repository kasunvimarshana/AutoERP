<?php

namespace App\MessageBroker\Drivers;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Synchronous in-process message broker — great for testing and single-server setups.
 * Handlers are invoked immediately when a message is published.
 */
class SyncMessageBroker implements MessageBrokerInterface
{
    /** @var array<string, callable[]> */
    private array $subscriptions = [];

    public function publish(string $topic, array $payload, array $options = []): void
    {
        Log::debug("[SyncMessageBroker] Publishing to topic: {$topic}", $payload);

        if (isset($this->subscriptions[$topic])) {
            foreach ($this->subscriptions[$topic] as $handler) {
                try {
                    $handler($payload);
                } catch (\Throwable $e) {
                    Log::error("[SyncMessageBroker] Handler error on topic {$topic}: " . $e->getMessage());
                    throw $e;
                }
            }
        }
    }

    public function subscribe(string $topic, callable $handler): void
    {
        $this->subscriptions[$topic][] = $handler;
        Log::debug("[SyncMessageBroker] Subscribed to topic: {$topic}");
    }

    public function broadcast(string $topic, array $payload): void
    {
        $this->publish($topic, $payload);
    }

    public function ack(string $messageId): void
    {
        // No-op for sync driver
    }

    public function nack(string $messageId, bool $requeue = true): void
    {
        // No-op for sync driver
    }

    public function getDriver(): string
    {
        return 'sync';
    }
}
