<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\MessageBroker\Drivers\RedisMessageBroker;
use App\MessageBroker\Drivers\SyncMessageBroker;
use InvalidArgumentException;

class MessageBrokerManager implements MessageBrokerInterface
{
    private MessageBrokerInterface $driver;
    private array $drivers = [];

    public function __construct()
    {
        $this->driver = $this->resolveDriver(config('app.message_broker_driver', 'sync'));
    }

    private function resolveDriver(string $name): MessageBrokerInterface
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $driver = match ($name) {
            'sync'  => new SyncMessageBroker(),
            'redis' => new RedisMessageBroker(),
            default => throw new InvalidArgumentException("Unsupported message broker driver: [{$name}]"),
        };

        $this->drivers[$name] = $driver;

        return $driver;
    }

    /**
     * Swap to a different driver at runtime.
     */
    public function driver(string $name): MessageBrokerInterface
    {
        return $this->resolveDriver($name);
    }

    // ─── Delegate to active driver ────────────────────────────────────────

    public function publish(string $topic, array $payload, array $options = []): void
    {
        $this->driver->publish($topic, $payload, $options);
    }

    public function subscribe(string $topic, callable $handler): void
    {
        $this->driver->subscribe($topic, $handler);
    }

    public function broadcast(string $topic, array $payload): void
    {
        $this->driver->broadcast($topic, $payload);
    }

    public function ack(string $messageId): void
    {
        $this->driver->ack($messageId);
    }

    public function nack(string $messageId, bool $requeue = true): void
    {
        $this->driver->nack($messageId, $requeue);
    }

    public function getDriver(): string
    {
        return $this->driver->getDriver();
    }
}
