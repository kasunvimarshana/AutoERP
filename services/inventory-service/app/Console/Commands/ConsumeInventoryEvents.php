<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Listeners\HandleProductCreated;
use App\Listeners\HandleProductDeleted;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class ConsumeInventoryEvents extends Command
{
    protected $signature = 'inventory:consume-events
        {--timeout=0 : Maximum seconds to run (0 = run forever)}';

    protected $description = 'Consume product events from RabbitMQ and handle inventory updates';

    private bool $shouldStop = false;

    /**
     * Routing keys this consumer handles.
     *
     * @var array<string, string>
     */
    private const ROUTING_KEYS = [
        'product.created' => HandleProductCreated::class,
        'product.deleted' => HandleProductDeleted::class,
    ];

    public function handle(): int
    {
        $this->info('Starting inventory event consumer...');

        // Register signal handlers for graceful shutdown (pcntl)
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGTERM, fn () => $this->shouldStop = true);
            pcntl_signal(SIGINT,  fn () => $this->shouldStop = true);
        }

        $config = config('rabbitmq');

        try {
            $connection = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                (float) $config['connection_timeout'],
                (float) $config['read_write_timeout'],
                null,
                false,
                (int) $config['heartbeat']
            );

            $channel = $connection->channel();

            // Declare the exchange
            $channel->exchange_declare(
                $config['exchange'],
                $config['exchange_type'],
                false,
                true,
                false
            );

            // Declare the service queue
            [$queueName] = $channel->queue_declare(
                'inventory-service-product-events',
                false,
                true,
                false,
                false
            );

            // Bind each routing key to the queue
            foreach (array_keys(self::ROUTING_KEYS) as $routingKey) {
                $channel->queue_bind($queueName, $config['exchange'], $routingKey);
                $this->info("Bound routing key: {$routingKey}");
            }

            // Only one unacknowledged message at a time per worker
            $channel->basic_qos(0, 1, false);

            $channel->basic_consume(
                $queueName,
                '',
                false,
                false, // manual ack
                false,
                false,
                function (AMQPMessage $message) use ($channel): void {
                    $this->processMessage($message, $channel);
                }
            );

            $this->info("Consumer ready. Listening on queue: {$queueName}");

            $timeout = (int) $this->option('timeout');
            $started = time();

            while ($channel->is_consuming() && ! $this->shouldStop) {
                $channel->wait(null, false, 1);

                if (extension_loaded('pcntl')) {
                    pcntl_signal_dispatch();
                }

                if ($timeout > 0 && (time() - $started) >= $timeout) {
                    $this->info('Timeout reached, stopping consumer.');
                    break;
                }
            }

            $channel->close();
            $connection->close();

            $this->info('Consumer stopped gracefully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Consumer error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Dispatch the incoming message to the appropriate listener.
     */
    private function processMessage(AMQPMessage $message, $channel): void
    {
        $routingKey = $message->getRoutingKey();

        $this->line("Received message with routing key: {$routingKey}");

        try {
            $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

            $listenerClass = self::ROUTING_KEYS[$routingKey] ?? null;

            if ($listenerClass === null) {
                $this->warn("No listener registered for routing key: {$routingKey}");
                $channel->basic_ack($message->getDeliveryTag());
                return;
            }

            /** @var HandleProductCreated|HandleProductDeleted $listener */
            $listener = app($listenerClass);
            $listener->handle($payload);

            $channel->basic_ack($message->getDeliveryTag());

            $this->info("Processed [{$routingKey}] message successfully.");
        } catch (\JsonException $e) {
            $this->error("Invalid JSON payload: {$e->getMessage()}");
            // Reject and don't requeue malformed messages
            $channel->basic_nack($message->getDeliveryTag(), false, false);
        } catch (BindingResolutionException $e) {
            $this->error("Listener resolution failed for [{$routingKey}]: {$e->getMessage()}");
            $channel->basic_nack($message->getDeliveryTag(), false, true);
        } catch (Throwable $e) {
            $this->error("Failed to process [{$routingKey}]: {$e->getMessage()}");
            // Requeue for retry
            $channel->basic_nack($message->getDeliveryTag(), false, true);
        }
    }
}
