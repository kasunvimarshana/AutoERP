<?php

namespace App\MessageBroker\Contracts;

interface MessageBrokerInterface
{
    public function publish(string $exchange, string $routingKey, array $message): void;

    public function subscribe(string $queue, callable $handler): void;

    public function disconnect(): void;
}
