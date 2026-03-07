<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PublishUserEventToRabbitMQ implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'rabbitmq-publisher';
    public int    $tries = 5;
    public int    $backoff = 5;

    public function handle(UserCreated|UserUpdated|UserDeleted $event): void
    {
        $payload = $this->buildPayload($event);
        $routingKey = $this->resolveRoutingKey($event);

        $this->publish($routingKey, $payload);
    }

    private function buildPayload(UserCreated|UserUpdated|UserDeleted $event): array
    {
        return match (true) {
            $event instanceof UserCreated => [
                'event'     => 'user.created',
                'tenant_id' => $event->user->tenant_id,
                'data'      => [
                    'id'       => $event->user->id,
                    'email'    => $event->user->email,
                    'name'     => $event->user->name,
                    'role'     => $event->user->role,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
            $event instanceof UserUpdated => [
                'event'     => 'user.updated',
                'tenant_id' => $event->user->tenant_id,
                'data'      => [
                    'id'      => $event->user->id,
                    'changes' => $event->changes,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
            $event instanceof UserDeleted => [
                'event'     => 'user.deleted',
                'tenant_id' => $event->tenantId,
                'data'      => [
                    'id'    => $event->userId,
                    'email' => $event->email,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        };
    }

    private function resolveRoutingKey(UserCreated|UserUpdated|UserDeleted $event): string
    {
        return match (true) {
            $event instanceof UserCreated => config('rabbitmq.routing_keys.user.created', 'user.created'),
            $event instanceof UserUpdated => config('rabbitmq.routing_keys.user.updated', 'user.updated'),
            $event instanceof UserDeleted => config('rabbitmq.routing_keys.user.deleted', 'user.deleted'),
        };
    }

    private function publish(string $routingKey, array $payload): void
    {
        $cfg = config('rabbitmq');

        try {
            $connection = new AMQPStreamConnection(
                $cfg['host'],
                $cfg['port'],
                $cfg['user'],
                $cfg['password'],
                $cfg['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                (float) $cfg['connection_timeout'],
                (float) $cfg['read_write_timeout'],
                null,
                (bool)  $cfg['keepalive'],
                (int)   $cfg['heartbeat'],
            );

            $channel = $connection->channel();

            $channel->exchange_declare(
                $cfg['exchange']['name'],
                $cfg['exchange']['type'],
                $cfg['exchange']['passive'],
                $cfg['exchange']['durable'],
                $cfg['exchange']['auto_delete'],
            );

            $message = new AMQPMessage(
                json_encode($payload, JSON_THROW_ON_ERROR),
                [
                    'content_type'  => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $channel->basic_publish($message, $cfg['exchange']['name'], $routingKey);

            $channel->close();
            $connection->close();

            Log::info('User event published to RabbitMQ', [
                'routing_key' => $routingKey,
                'event'       => $payload['event'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish user event to RabbitMQ', [
                'routing_key' => $routingKey,
                'error'       => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
