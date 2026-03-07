<?php
namespace App\Console\Commands;

use App\Saga\NotificationSagaHandler;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class NotificationCommandConsumer extends Command
{
    protected $signature   = 'notification:consume-commands';
    protected $description = 'Consume notification commands from RabbitMQ';

    public function handle(): void
    {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost'),
        );

        $channel = $connection->channel();
        $exchange = config('rabbitmq.events_exchange', 'saga.events');
        $queue    = config('rabbitmq.commands_queue',  'notification.commands');

        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, 'notification.commands.*');

        $handler = new NotificationSagaHandler(
            app(\App\Services\NotificationService::class),
            $channel,
            $exchange,
        );

        $this->info("Consuming from [{$queue}] …");

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue, '', false, false, false, false,
            function (AMQPMessage $msg) use ($handler) {
                $payload = json_decode($msg->getBody(), true);
                $command = $payload['command'] ?? '';
                $this->info("Command: {$command}");

                match ($command) {
                    'SEND_ORDER_CONFIRMATION' => $handler->handleSendOrderConfirmation($payload),
                    'SEND_ORDER_CANCELLATION' => $handler->handleSendOrderCancellation($payload),
                    default                   => $this->warn("Unknown command: {$command}"),
                };

                $msg->ack();
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
