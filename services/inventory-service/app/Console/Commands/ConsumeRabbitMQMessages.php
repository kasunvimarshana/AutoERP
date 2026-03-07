<?php

namespace App\Console\Commands;

use App\Listeners\HandleProductCreatedEvent;
use App\Listeners\HandleProductDeletedEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeRabbitMQMessages extends Command
{
    protected $signature = 'rabbitmq:consume
                            {--queue= : Override the queue name}
                            {--timeout=0 : Consumer timeout in seconds (0 = run forever)}';

    protected $description = 'Consume product events from RabbitMQ and dispatch handlers';

    public function __construct(
        private readonly HandleProductCreatedEvent $productCreatedHandler,
        private readonly HandleProductDeletedEvent $productDeletedHandler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $cfg      = config('rabbitmq');
        $queueCfg = $cfg['queues']['product_events'];
        $queueName = $this->option('queue') ?: $queueCfg['name'];

        $this->info("Connecting to RabbitMQ at {$cfg['host']}:{$cfg['port']}…");

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

            // Declare the exchange
            $channel->exchange_declare(
                $cfg['exchange']['name'],
                $cfg['exchange']['type'],
                $cfg['exchange']['passive'],
                $cfg['exchange']['durable'],
                $cfg['exchange']['auto_delete'],
            );

            // Declare the queue
            $channel->queue_declare(
                $queueName,
                $queueCfg['passive'],
                $queueCfg['durable'],
                $queueCfg['exclusive'],
                $queueCfg['auto_delete'],
            );

            // Bind routing keys
            foreach ($cfg['bindings'] as $routingKey) {
                $channel->queue_bind($queueName, $cfg['exchange']['name'], $routingKey);
                $this->info("Bound queue [{$queueName}] to routing key [{$routingKey}]");
            }

            // One message at a time for fair dispatch
            $channel->basic_qos(0, 1, false);

            $callback = function (AMQPMessage $message) use ($channel): void {
                $this->processMessage($message, $channel);
            };

            $channel->basic_consume(
                $queueName,
                '',     // consumer tag
                false,  // no-local
                false,  // no-ack (manual ack)
                false,  // exclusive
                false,  // no-wait
                $callback,
            );

            $this->info("Listening on queue [{$queueName}]. Press Ctrl+C to stop.");

            $timeout = (int) $this->option('timeout');

            while ($channel->is_consuming()) {
                $channel->wait(null, false, $timeout ?: null);
            }

            $channel->close();
            $connection->close();
        } catch (\Throwable $e) {
            $this->error("RabbitMQ consumer error: {$e->getMessage()}");
            Log::error('RabbitMQ consumer crashed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function processMessage(AMQPMessage $message, $channel): void
    {
        try {
            $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $event   = $payload['event'] ?? 'unknown';

            $this->info("[{$event}] Received message for tenant [{$payload['tenant_id'] ?? '?'}]");

            match ($event) {
                'product.created' => $this->productCreatedHandler->handle($payload),
                'product.deleted' => $this->productDeletedHandler->handle($payload),
                default           => $this->warn("Unhandled event type: [{$event}]"),
            };

            // Acknowledge successful processing
            $channel->basic_ack($message->getDeliveryTag());

            Log::info('RabbitMQ message processed', ['event' => $event]);
        } catch (\Throwable $e) {
            $this->error("Failed to process message: {$e->getMessage()}");

            Log::error('Failed to process RabbitMQ message', [
                'body'  => $message->getBody(),
                'error' => $e->getMessage(),
            ]);

            // Reject and re-queue once; if it fails again, send to dead-letter
            $channel->basic_nack($message->getDeliveryTag(), false, false);
        }
    }
}
