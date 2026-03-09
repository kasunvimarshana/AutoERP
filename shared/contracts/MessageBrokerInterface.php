<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Message Broker Interface
 * 
 * Pluggable message broker abstraction supporting Kafka, RabbitMQ, and other brokers.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to a topic/exchange/queue.
     */
    public function publish(string $topic, array $message, array $options = []): bool;

    /**
     * Subscribe to a topic/exchange/queue with a callback handler.
     */
    public function subscribe(string $topic, callable $handler, array $options = []): void;

    /**
     * Acknowledge message processing.
     */
    public function acknowledge(mixed $message): void;

    /**
     * Reject/nack a message.
     */
    public function reject(mixed $message, bool $requeue = false): void;

    /**
     * Check broker connection health.
     */
    public function healthCheck(): bool;
}
