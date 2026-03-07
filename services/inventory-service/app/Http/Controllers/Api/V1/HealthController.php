<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ProductClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(ProductClientService $productClient): JsonResponse
    {
        $checks = [
            'database'        => $this->checkDatabase(),
            'redis'           => $this->checkRedis(),
            'rabbitmq'        => $this->checkRabbitMQ(),
            'product_service' => $this->checkProductService($productClient),
        ];

        $allHealthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'service' => 'inventory-service',
            'status'  => $allHealthy ? 'healthy' : 'degraded',
            'checks'  => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Database connection established.'];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $pong = Cache::store('redis')->get('health_ping');
            Cache::store('redis')->put('health_ping', 'pong', 10);

            return ['status' => 'ok', 'message' => 'Redis is reachable.'];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'message' => $e->getMessage()];
        }
    }

    private function checkRabbitMQ(): array
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
                2.0, // short timeout for health check
                2.0,
            );
            $connection->close();

            return ['status' => 'ok', 'message' => 'RabbitMQ is reachable.'];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'message' => $e->getMessage()];
        }
    }

    private function checkProductService(ProductClientService $productClient): array
    {
        $alive = $productClient->ping();

        return $alive
            ? ['status' => 'ok',   'message' => 'Product service is reachable.']
            : ['status' => 'fail', 'message' => 'Product service is unreachable.'];
    }
}
