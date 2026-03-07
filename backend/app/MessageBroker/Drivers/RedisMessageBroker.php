<?php

namespace App\MessageBroker\Drivers;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;
use Predis\Client as Redis;

/**
 * Redis Pub/Sub message broker driver.
 * Uses Redis PUBLISH/SUBSCRIBE for inter-service communication.
 */
class RedisMessageBroker implements MessageBrokerInterface
{
    private Redis $publisher;
    private Redis $subscriber;

    /** @var array<string, callable[]> */
    private array $handlers = [];

    public function __construct()
    {
        $config = [
            'scheme'   => 'tcp',
            'host'     => config('database.redis.default.host', '127.0.0.1'),
            'port'     => (int) config('database.redis.default.port', 6379),
            'password' => config('database.redis.default.password'),
            'database' => (int) config('database.redis.default.database', 0),
        ];

        $this->publisher  = new Redis($config);
        $this->subscriber = new Redis($config);
    }

    public function publish(string $topic, array $payload, array $options = []): void
    {
        $message = json_encode([
            'id'         => \Illuminate\Support\Str::uuid()->toString(),
            'topic'      => $topic,
            'payload'    => $payload,
            'published_at' => now()->toIso8601String(),
            'tenant_id'  => app()->bound('current_tenant') ? app('current_tenant')->id : null,
        ]);

        $count = $this->publisher->publish($topic, $message);

        Log::debug("[RedisMessageBroker] Published to '{$topic}'. Receivers: {$count}");
    }

    public function subscribe(string $topic, callable $handler): void
    {
        $this->handlers[$topic][] = $handler;

        Log::debug("[RedisMessageBroker] Registered handler for topic: {$topic}");
    }

    /**
     * Start listening — blocks the process.
     * Call this in a dedicated Artisan command / worker.
     */
    public function listen(): void
    {
        $topics = array_keys($this->handlers);

        if (empty($topics)) {
            return;
        }

        $this->subscriber->subscribe($topics, function ($message, $channel, $payload) {
            $decoded = json_decode($payload, true);

            if (!$decoded) {
                Log::warning("[RedisMessageBroker] Failed to decode message on '{$channel}'");
                return;
            }

            if (isset($this->handlers[$channel])) {
                foreach ($this->handlers[$channel] as $handler) {
                    try {
                        $handler($decoded['payload'] ?? $decoded);
                    } catch (\Throwable $e) {
                        Log::error("[RedisMessageBroker] Handler error on '{$channel}': " . $e->getMessage());
                    }
                }
            }
        });
    }

    public function broadcast(string $topic, array $payload): void
    {
        $this->publish($topic, $payload);
    }

    public function ack(string $messageId): void
    {
        // Redis Pub/Sub is fire-and-forget; no explicit ACK needed
    }

    public function nack(string $messageId, bool $requeue = true): void
    {
        // Redis Pub/Sub is fire-and-forget; no NACK support
    }

    public function getDriver(): string
    {
        return 'redis';
    }
}
