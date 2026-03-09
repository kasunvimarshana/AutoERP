<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Contracts;

/**
 * Message Broker Interface
 *
 * Pluggable broker abstraction. Implement to support any message broker.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to the specified topic/queue/exchange.
     *
     * @param string $topic
     * @param array<string, mixed> $message
     * @param array<string, mixed> $options
     */
    public function publish(string $topic, array $message, array $options = []): bool;

    /**
     * Subscribe to a topic/queue with a handler callback.
     *
     * @param string $topic
     * @param callable $handler  Receives the decoded message array
     * @param array<string, mixed> $options
     */
    public function subscribe(string $topic, callable $handler, array $options = []): void;

    /**
     * Acknowledge successful message processing.
     */
    public function acknowledge(mixed $message): void;

    /**
     * Reject/nack a message, optionally requeueing it.
     */
    public function reject(mixed $message, bool $requeue = false): void;

    /**
     * Health check for the broker connection.
     */
    public function healthCheck(): bool;
}
