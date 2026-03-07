<?php
namespace App\Infrastructure\MessageBroker;

use App\Interfaces\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

class NullMessageBroker implements MessageBrokerInterface
{
    public function publish(string $topic, array $message, array $options = []): bool
    {
        Log::info("NullMessageBroker: publish to {$topic}", $message);
        return true;
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        Log::info("NullMessageBroker: subscribe to {$topic}");
    }

    public function acknowledge(mixed $message): void {}

    public function reject(mixed $message, bool $requeue = false): void {}

    public function isConnected(): bool
    {
        return true;
    }

    public function disconnect(): void {}
}
