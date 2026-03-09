<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Brokers;

use App\Infrastructure\Messaging\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Log Message Broker
 *
 * Development/testing broker that logs messages instead of publishing them.
 * Use as default broker when no real broker is configured.
 */
class LogBroker implements MessageBrokerInterface
{
    public function publish(string $topic, array $message, array $options = []): bool
    {
        Log::info("[MessageBroker] Published to topic: {$topic}", [
            'message' => $message,
            'options' => $options,
        ]);

        return true;
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        Log::info("[MessageBroker] Subscribed to topic: {$topic}");
    }

    public function acknowledge(mixed $message): void
    {
        Log::debug('[MessageBroker] Message acknowledged');
    }

    public function reject(mixed $message, bool $requeue = false): void
    {
        Log::debug("[MessageBroker] Message rejected (requeue: " . ($requeue ? 'true' : 'false') . ")");
    }

    public function healthCheck(): bool
    {
        return true;
    }
}
