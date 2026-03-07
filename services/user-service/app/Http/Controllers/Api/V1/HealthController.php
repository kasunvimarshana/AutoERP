<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $status = [
            'service'   => 'user-service',
            'status'    => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks'    => [
                'database' => $this->checkDatabase(),
                'redis'    => $this->checkRedis(),
                'rabbitmq' => $this->checkRabbitMQ(),
            ],
        ];

        $isHealthy = collect($status['checks'])->every(fn ($c) => $c['status'] === 'ok');

        if (! $isHealthy) {
            $status['status'] = 'degraded';
        }

        return response()->json($status, $isHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $latencyMs = $this->measureMs(fn () => DB::select('SELECT 1'));

            return ['status' => 'ok', 'latency_ms' => $latencyMs];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $latencyMs = $this->measureMs(function () {
                Cache::put('health_check_ping', 'pong', 5);
                Cache::get('health_check_ping');
            });

            return ['status' => 'ok', 'latency_ms' => $latencyMs];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRabbitMQ(): array
    {
        $cfg = config('rabbitmq');

        try {
            $latencyMs = $this->measureMs(function () use ($cfg) {
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
                    2.0,
                    2.0,
                );
                $connection->close();
            });

            return ['status' => 'ok', 'latency_ms' => $latencyMs];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function measureMs(callable $fn): float
    {
        $start = microtime(true);
        $fn();

        return round((microtime(true) - $start) * 1000, 2);
    }
}
