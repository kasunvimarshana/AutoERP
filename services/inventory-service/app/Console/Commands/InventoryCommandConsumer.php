<?php

namespace App\Console\Commands;

use App\Saga\InventorySagaHandler;
use App\Services\RabbitMQService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * InventoryCommandConsumer
 *
 * Long-running Artisan command that subscribes to the inventory.commands queue
 * and routes each message to the appropriate InventorySagaHandler method.
 *
 * Usage:
 *   php artisan inventory:consume-commands
 *   php artisan inventory:consume-commands --queue=inventory.commands
 */
class InventoryCommandConsumer extends Command
{
    protected $signature = 'inventory:consume-commands
        {--queue= : RabbitMQ queue to consume (defaults to env INVENTORY_COMMANDS_QUEUE)}
        {--timeout=0 : Maximum seconds to run (0 = unlimited)}';

    protected $description = 'Consume SAGA inventory commands from RabbitMQ (RESERVE / RELEASE / FULFILL)';

    private const COMMAND_HANDLERS = [
        'RESERVE_INVENTORY' => 'handleReserveInventory',
        'RELEASE_INVENTORY' => 'handleReleaseInventory',
        'FULFILL_INVENTORY' => 'handleFulfillInventory',
    ];

    public function __construct(
        private readonly RabbitMQService      $rabbitMQ,
        private readonly InventorySagaHandler $sagaHandler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $queue   = $this->option('queue') ?: env('INVENTORY_COMMANDS_QUEUE', 'inventory.commands');
        $timeout = (int) $this->option('timeout');

        $this->info("[InventoryCommandConsumer] Starting. Listening on queue: {$queue}");
        Log::info('[InventoryCommandConsumer] Starting.', ['queue' => $queue, 'timeout' => $timeout]);

        if ($timeout > 0) {
            $this->scheduleShutdown($timeout);
        }

        $attempt = 0;

        while (true) {
            try {
                $attempt++;
                $this->info("[InventoryCommandConsumer] Connecting (attempt #{$attempt})...");

                $this->rabbitMQ->subscribe(
                    $queue,
                    fn (array $payload) => $this->dispatch($payload)
                );

                $this->info('[InventoryCommandConsumer] Channel closed. Reconnecting...');
            } catch (Throwable $e) {
                Log::error('[InventoryCommandConsumer] Error during consumption.', [
                    'error' => $e->getMessage(),
                ]);
                $this->error("[InventoryCommandConsumer] Error: {$e->getMessage()}");
            } finally {
                $this->rabbitMQ->disconnect();
            }

            $delay = min(30, $attempt * 2);
            $this->info("[InventoryCommandConsumer] Reconnecting in {$delay}s...");
            sleep($delay);
        }

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function dispatch(array $payload): bool
    {
        $command = $payload['command'] ?? $payload['type'] ?? null;

        if ($command === null) {
            Log::warning('[InventoryCommandConsumer] Message missing command/type field.', $payload);
            return true; // Ack to avoid dead-lettering a malformed message.
        }

        $method = self::COMMAND_HANDLERS[$command] ?? null;

        if ($method === null) {
            Log::warning("[InventoryCommandConsumer] Unknown command '{$command}' – ignoring.", $payload);
            return true;
        }

        Log::info("[InventoryCommandConsumer] Dispatching command '{$command}'.", [
            'saga_id'  => $payload['saga_id']  ?? $payload['sagaId']  ?? null,
            'order_id' => $payload['order_id'] ?? $payload['orderId'] ?? null,
        ]);

        $this->sagaHandler->{$method}($payload);

        return true;
    }

    private function scheduleShutdown(int $seconds): void
    {
        if (!function_exists('pcntl_alarm')) {
            return;
        }

        pcntl_signal(SIGALRM, function () {
            Log::info('[InventoryCommandConsumer] Timeout reached – shutting down gracefully.');
            $this->info('[InventoryCommandConsumer] Timeout reached – exiting.');
            exit(0);
        });

        pcntl_alarm($seconds);
    }
}
