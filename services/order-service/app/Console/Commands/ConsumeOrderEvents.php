<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class ConsumeOrderEvents extends Command
{
    protected $signature = 'order:consume-events
        {--timeout=0 : Maximum seconds to run (0 = run forever)}';

    protected $description = 'Consume inventory and cross-service events from RabbitMQ';

    private bool $shouldStop = false;

    /**
     * Routing keys this consumer handles, mapped to method names on this class.
     *
     * @var array<string, string>
     */
    private const ROUTING_KEYS = [
        'inventory.updated' => 'handleInventoryUpdated',
    ];

    public function handle(): int
    {
        $this->info('Starting order event consumer...');

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

            // Declare the inventory exchange so we can bind to it
            $channel->exchange_declare(
                'inventory.events',
                'topic',
                false,
                true,
                false
            );

            [$queueName] = $channel->queue_declare(
                'order-service-inventory-events',
                false,
                true,
                false,
                false
            );

            foreach (array_keys(self::ROUTING_KEYS) as $routingKey) {
                $channel->queue_bind($queueName, 'inventory.events', $routingKey);
                $this->info("Bound routing key: {$routingKey}");
            }

            $channel->basic_qos(0, 1, false);

            $channel->basic_consume(
                $queueName,
                '',
                false,
                false,
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

    private function processMessage(AMQPMessage $message, $channel): void
    {
        $routingKey = $message->getRoutingKey();

        $this->line("Received message with routing key: {$routingKey}");

        try {
            $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

            $method = self::ROUTING_KEYS[$routingKey] ?? null;

            if ($method === null) {
                $this->warn("No handler registered for routing key: {$routingKey}");
                $channel->basic_ack($message->getDeliveryTag());
                return;
            }

            $this->{$method}($payload);

            $channel->basic_ack($message->getDeliveryTag());

            $this->info("Processed [{$routingKey}] message successfully.");
        } catch (\JsonException $e) {
            $this->error("Invalid JSON payload: {$e->getMessage()}");
            $channel->basic_nack($message->getDeliveryTag(), false, false);
        } catch (Throwable $e) {
            $this->error("Failed to process [{$routingKey}]: {$e->getMessage()}");
            $channel->basic_nack($message->getDeliveryTag(), false, true);
        }
    }

    /**
     * Handle inventory.updated event.
     *
     * Syncs order item status when inventory is confirmed as consumed.
     *
     * @param  array<string, mixed> $payload
     */
    private function handleInventoryUpdated(array $payload): void
    {
        $referenceType = $payload['reference_type'] ?? null;
        $referenceId   = $payload['reference_id']   ?? null;

        // Only act on inventory changes tied to an order
        if ($referenceType !== 'order' || empty($referenceId)) {
            return;
        }

        $orderId = (int) $referenceId;

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = app(OrderRepositoryInterface::class);

        $order = $orderRepository->findById($orderId, withItems: true);

        if ($order === null) {
            $this->warn("handleInventoryUpdated: order {$orderId} not found.");
            return;
        }

        $transactionType = $payload['transaction_type'] ?? null;

        // If inventory was committed (sale), mark the order as processing
        if ($transactionType === 'sale' && $order->status === Order::STATUS_CONFIRMED) {
            $orderRepository->update($orderId, ['status' => Order::STATUS_PROCESSING]);

            $this->info("Order {$orderId} status advanced to 'processing'.");
        }
    }
}
