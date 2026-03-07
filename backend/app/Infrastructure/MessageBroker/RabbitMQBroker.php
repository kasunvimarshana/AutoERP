<?php
namespace App\Infrastructure\MessageBroker;

use App\Interfaces\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

class RabbitMQBroker implements MessageBrokerInterface
{
    private mixed $connection = null;
    private mixed $channel = null;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        try {
            Log::info('RabbitMQBroker: connecting to ' . config('services.rabbitmq.host', 'localhost'));
        } catch (\Exception $e) {
            Log::error('RabbitMQBroker: connection failed - ' . $e->getMessage());
        }
    }

    public function publish(string $topic, array $message, array $options = []): bool
    {
        Log::info("RabbitMQBroker: publish to {$topic}", $message);
        return true;
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        Log::info("RabbitMQBroker: subscribe to {$topic}");
    }

    public function acknowledge(mixed $message): void {}

    public function reject(mixed $message, bool $requeue = false): void {}

    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    public function disconnect(): void
    {
        $this->connection = null;
        $this->channel = null;
    }
}
