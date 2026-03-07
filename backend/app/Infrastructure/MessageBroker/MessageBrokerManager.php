<?php

declare(strict_types=1);

namespace App\Infrastructure\MessageBroker;

use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use App\Infrastructure\MessageBroker\Drivers\KafkaMessageBroker;
use App\Infrastructure\MessageBroker\Drivers\RabbitMQMessageBroker;
use Illuminate\Support\Manager;

/**
 * Message broker manager — resolves the configured driver at runtime.
 *
 * Configured via config/broker.php  (`BROKER_DRIVER` env var).
 * Extend by registering custom drivers with `extend()`.
 */
class MessageBrokerManager extends Manager implements MessageBrokerInterface
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('broker.driver', 'null');
    }

    protected function createKafkaDriver(): KafkaMessageBroker
    {
        return new KafkaMessageBroker($this->config->get('broker.drivers.kafka', []));
    }

    protected function createRabbitmqDriver(): RabbitMQMessageBroker
    {
        return new RabbitMQMessageBroker($this->config->get('broker.drivers.rabbitmq', []));
    }

    protected function createNullDriver(): MessageBrokerInterface
    {
        return new class implements MessageBrokerInterface {
            public function publish(string $topic, array $payload, array $options = []): void {}
            public function subscribe(array $topics, callable $callback, array $options = []): void {}
            public function acknowledge(mixed $messageId): void {}
            public function reject(mixed $messageId, bool $requeue = false): void {}
            public function isHealthy(): bool { return true; }
            public function getDriver(): string { return 'null'; }
        };
    }

    // -------------------------------------------------------------------------
    // Delegate MessageBrokerInterface methods to the resolved driver
    // -------------------------------------------------------------------------

    public function publish(string $topic, array $payload, array $options = []): void
    {
        $this->driver()->publish($topic, $payload, $options);
    }

    public function subscribe(array $topics, callable $callback, array $options = []): void
    {
        $this->driver()->subscribe($topics, $callback, $options);
    }

    public function acknowledge(mixed $messageId): void
    {
        $this->driver()->acknowledge($messageId);
    }

    public function reject(mixed $messageId, bool $requeue = false): void
    {
        $this->driver()->reject($messageId, $requeue);
    }

    public function isHealthy(): bool
    {
        return $this->driver()->isHealthy();
    }

    public function getDriver(): string
    {
        return $this->driver()->getDriver();
    }
}
