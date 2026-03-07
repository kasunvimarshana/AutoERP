<?php

namespace App\Console\Commands;

use App\Listeners\SagaEventListener;
use App\Services\RabbitMQService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SagaEventConsumer
 *
 * Long-running Artisan command that subscribes to the saga.orchestrator.replies
 * queue and routes incoming events to SagaEventListener → OrderSagaOrchestrator.
 *
 * Usage:
 *   php artisan saga:consume-events
 *   php artisan saga:consume-events --queue=saga.orchestrator.replies
 */
class SagaEventConsumer extends Command
{
    protected $signature = 'saga:consume-events
        {--queue=saga.orchestrator.replies : RabbitMQ queue to consume}
        {--timeout=0 : Maximum seconds to run (0 = unlimited)}';

    protected $description = 'Consume Saga reply events from RabbitMQ and advance/compensate sagas';

    public function __construct(
        private readonly RabbitMQService  $rabbitMQ,
        private readonly SagaEventListener $listener,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $queue   = $this->option('queue');
        $timeout = (int) $this->option('timeout');

        $this->info("[SagaEventConsumer] Starting. Listening on queue: {$queue}");
        Log::info('[SagaEventConsumer] Starting.', ['queue' => $queue, 'timeout' => $timeout]);

        if ($timeout > 0) {
            $this->scheduleShutdown($timeout);
        }

        $attempt = 0;

        // Re-connect loop: if RabbitMQ drops the connection, retry indefinitely.
        while (true) {
            try {
                $attempt++;

                $this->info("[SagaEventConsumer] Connecting (attempt #{$attempt})...");

                $this->rabbitMQ->subscribe(
                    $queue,
                    fn (array $payload, $message) => $this->listener->handle($payload, $message)
                );

                // subscribe() blocks until the channel closes.
                $this->info('[SagaEventConsumer] Channel closed. Reconnecting...');
            } catch (Throwable $e) {
                Log::error('[SagaEventConsumer] Error during consumption.', [
                    'error' => $e->getMessage(),
                ]);
                $this->error("[SagaEventConsumer] Error: {$e->getMessage()}");
            } finally {
                $this->rabbitMQ->disconnect();
            }

            // Back-off before reconnect.
            $delay = min(30, $attempt * 2);
            $this->info("[SagaEventConsumer] Reconnecting in {$delay}s...");
            sleep($delay);
        }

        return self::SUCCESS;
    }

    /**
     * Install a SIGALRM handler to gracefully exit after a given timeout.
     * Useful for memory leak prevention in long-running workers.
     */
    private function scheduleShutdown(int $seconds): void
    {
        if (! function_exists('pcntl_alarm')) {
            return;
        }

        pcntl_signal(SIGALRM, function () {
            Log::info('[SagaEventConsumer] Timeout reached – shutting down gracefully.');
            $this->info('[SagaEventConsumer] Timeout reached – exiting.');
            exit(0);
        });

        pcntl_alarm($seconds);
    }
}
