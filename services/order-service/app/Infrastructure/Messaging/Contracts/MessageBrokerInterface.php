<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Contracts;

interface MessageBrokerInterface
{
    public function publish(string $topic, array $message, array $options = []): bool;
    public function subscribe(string $topic, callable $handler, array $options = []): void;
    public function acknowledge(mixed $message): void;
    public function reject(mixed $message, bool $requeue = false): void;
    public function healthCheck(): bool;
}
