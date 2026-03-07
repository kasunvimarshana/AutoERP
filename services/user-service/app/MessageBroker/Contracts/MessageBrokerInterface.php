<?php

namespace App\MessageBroker\Contracts;

interface MessageBrokerInterface
{
    /**
     * Publish a message to a topic / exchange / queue.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $topic, array $payload): bool;

    /**
     * Subscribe to a topic and invoke $callback for every message received.
     * The callback receives the decoded payload array as its first argument.
     *
     * @param  callable(array): void  $callback
     */
    public function subscribe(string $topic, callable $callback): void;

    /**
     * Cleanly close the connection to the broker.
     */
    public function disconnect(): void;
}
