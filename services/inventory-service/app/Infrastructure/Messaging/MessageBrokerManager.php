<?php
namespace App\Infrastructure\Messaging;
use App\Domain\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\App;

class MessageBrokerManager implements MessageBrokerInterface
{
    private ?MessageBrokerInterface $driver = null;

    public function resolve(?string $forceDriver = null): MessageBrokerInterface
    {
        $driver = $forceDriver ?? config('env.BROKER_DRIVER', config('queue.default', 'rabbitmq'));
        return match ($driver) {
            'kafka'    => App::make(KafkaBroker::class),
            default    => App::make(RabbitMQBroker::class),
        };
    }

    private function driver(): MessageBrokerInterface
    {
        if (!$this->driver) $this->driver = $this->resolve();
        return $this->driver;
    }

    public function publish(string $topic, string $event, array $payload, array $options = []): bool
    { return $this->driver()->publish($topic, $event, $payload, $options); }

    public function publishBatch(string $topic, array $messages, array $options = []): bool
    { return $this->driver()->publishBatch($topic, $messages, $options); }

    public function subscribe(string $topic, callable $callback, array $options = []): void
    { $this->driver()->subscribe($topic, $callback, $options); }

    public function acknowledge(mixed $message): void { $this->driver()->acknowledge($message); }
    public function nack(mixed $message, bool $requeue = false): void { $this->driver()->nack($message, $requeue); }
    public function isConnected(): bool { return $this->driver()->isConnected(); }
    public function disconnect(): void { $this->driver()->disconnect(); }
}
