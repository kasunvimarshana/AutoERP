<?php

namespace App\Domain\Contracts;

interface MessageBrokerInterface
{
    /**
     * Publish a single event to the broker.
     *
     * @param string $topic    Exchange name or Kafka topic.
     * @param string $event    Event type / routing key.
     * @param array  $payload  Event payload (will be JSON-encoded).
     * @param array  $options  Additional options (headers, priority, etc.)
     */
    public function publish(string $topic, string $event, array $payload, array $options = []): bool;

    /**
     * Publish a batch of events atomically (or as fast-as-possible).
     *
     * @param string               $topic
     * @param array<array{event: string, payload: array}> $messages
     * @param array  $options
     */
    public function publishBatch(string $topic, array $messages, array $options = []): bool;

    /**
     * Subscribe to a topic and invoke the callback for each message.
     *
     * @param string   $topic
     * @param callable $callback  function(array $payload, mixed $message): void
     * @param array    $options
     */
    public function subscribe(string $topic, callable $callback, array $options = []): void;

    /**
     * Acknowledge successful processing of a message.
     */
    public function acknowledge(mixed $message): void;

    /**
     * Negative-acknowledge a message (requeue or dead-letter).
     */
    public function nack(mixed $message, bool $requeue = false): void;

    /**
     * Check if the broker connection is alive.
     */
    public function isConnected(): bool;

    /**
     * Gracefully disconnect.
     */
    public function disconnect(): void;
}
