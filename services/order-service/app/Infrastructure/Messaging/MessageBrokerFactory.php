<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Infrastructure\Messaging\Brokers\KafkaBroker;
use App\Infrastructure\Messaging\Brokers\LogBroker;
use App\Infrastructure\Messaging\Brokers\RabbitMQBroker;
use App\Infrastructure\Messaging\Contracts\MessageBrokerInterface;
use InvalidArgumentException;

/**
 * Message Broker Factory
 */
class MessageBrokerFactory
{
    private ?MessageBrokerInterface $instance = null;

    public function getBroker(): MessageBrokerInterface
    {
        if ($this->instance !== null) {
            return $this->instance;
        }

        $driver = config('messaging.driver', 'log');

        $this->instance = match ($driver) {
            'rabbitmq' => app(RabbitMQBroker::class),
            'kafka' => app(KafkaBroker::class),
            'log' => app(LogBroker::class),
            default => throw new InvalidArgumentException("Unsupported message broker: {$driver}"),
        };

        return $this->instance;
    }

    public function setBroker(MessageBrokerInterface $broker): void
    {
        $this->instance = $broker;
    }
}
