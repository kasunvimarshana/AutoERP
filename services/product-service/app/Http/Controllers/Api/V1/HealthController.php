<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InventoryClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class HealthController extends Controller
{
    public function __construct(private readonly InventoryClientService $inventoryClient) {}

    /**
     * GET /api/v1/health
     * Returns status of DB, Redis, RabbitMQ, and inventory-service.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database'          => $this->checkDatabase(),
            'redis'             => $this->checkRedis(),
            'rabbitmq'          => $this->checkRabbitMQ(),
            'inventory_service' => $this->checkInventoryService(),
        ];

        $allHealthy = collect($checks)->every(fn($c) => $c['status'] === 'ok');

        return response()->json([
            'status'    => $allHealthy ? 'ok' : 'degraded',
            'service'   => 'product-service',
            'timestamp' => now()->toIso8601String(),
            'checks'    => $checks,
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            return ['status' => 'ok', 'latency_ms' => $this->measureMs(fn() => DB::select('SELECT 1'))];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Cache::store('redis')->put('health_check', 1, 5);
            Cache::store('redis')->get('health_check');
            $latency = (int) ((microtime(true) - $start) * 1000);
            return ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function checkRabbitMQ(): array
    {
        try {
            $config = config('rabbitmq');
            $start  = microtime(true);

            $conn = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                3.0,
                3.0,
            );
            $conn->close();

            $latency = (int) ((microtime(true) - $start) * 1000);
            return ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function checkInventoryService(): array
    {
        try {
            $start   = microtime(true);
            $healthy = $this->inventoryClient->ping();
            $latency = (int) ((microtime(true) - $start) * 1000);

            return $healthy
                ? ['status' => 'ok', 'latency_ms' => $latency]
                : ['status' => 'error', 'error' => 'inventory-service returned non-200'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function measureMs(callable $fn): int
    {
        $start = microtime(true);
        $fn();
        return (int) ((microtime(true) - $start) * 1000);
    }
}
