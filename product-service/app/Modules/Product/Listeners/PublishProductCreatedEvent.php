<?php

namespace App\Modules\Product\Listeners;

use App\Modules\Product\Events\ProductCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
// use PhpAmqpLib\Connection\AMQPStreamConnection;
// use PhpAmqpLib\Message\AMQPMessage;

class PublishProductCreatedEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public $connection = 'rabbitmq'; // Using a RabbitMQ queue connection configured in config/queue.php

    /**
     * Handle the event.
     */
    public function handle(ProductCreated $event): void
    {
        // Typically, we serialize the DTO or Model into JSON.
        $payload = json_encode([
            'event' => 'ProductCreated',
            'data' => $event->product->toArray(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Using Laravel's Queue facade with RabbitMQ driver:
        // This pushes to the appropriate exchange setup in the rabbitmq config.
        Log::info('Publishing ProductCreated event to RabbitMQ', ['payload' => $payload]);

        // Simulated raw AMQP publish (if using raw php-amqplib rather than Laravel Queues):
        /*
        $connection = new AMQPStreamConnection(env('RABBITMQ_HOST'), env('RABBITMQ_PORT'), env('RABBITMQ_USER'), env('RABBITMQ_PASSWORD'));
        $channel = $connection->channel();
        $channel->exchange_declare('domain_events', 'topic', false, true, false);

        $msg = new AMQPMessage($payload);
        $channel->basic_publish($msg, 'domain_events', 'product.created');

        $channel->close();
        $connection->close();
        */
    }
}
