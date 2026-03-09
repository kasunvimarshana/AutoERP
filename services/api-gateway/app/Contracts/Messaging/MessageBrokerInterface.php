<?php

declare(strict_types=1);

namespace App\Contracts\Messaging;

/**
 * Message Broker Interface
 *
 * Pluggable message broker abstraction supporting Kafka, RabbitMQ,
 * or any other messaging system without changing business logic.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to a topic/exchange.
     *
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $options
     */
    public function publish(string $topic, array $message, array $options = []): bool;

    /**
     * Subscribe to a topic/queue and process messages.
     *
     * @param  callable(array): void  $handler
     */
    public function subscribe(string $topic, callable $handler, array $options = []): void;

    /**
     * Acknowledge a message (mark as processed).
     */
    public function acknowledge(mixed $message): void;

    /**
     * Reject/Nack a message (for retry or DLQ).
     */
    public function reject(mixed $message, bool $requeue = false): void;

    /**
     * Check broker connectivity.
     */
    public function isConnected(): bool;

    /**
     * Close the broker connection.
     */
    public function disconnect(): void;
}
