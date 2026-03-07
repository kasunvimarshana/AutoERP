<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckService
{
    public function check(): array
    {
        return [
            'status'    => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services'  => [
                'database' => $this->checkDatabase(),
                'redis'    => $this->checkRedis(),
                'rabbitmq' => $this->checkRabbitMQ(),
            ],
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $latency = $this->measureLatency(fn() => DB::select('SELECT 1'));
            return ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            Log::warning('Health check: database failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $latency = $this->measureLatency(fn() => Cache::store('redis')->get('health_check'));
            return ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            Log::warning('Health check: redis failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRabbitMQ(): array
    {
        try {
            $config     = config('queue.connections.rabbitmq');
            $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['login'],
                $config['password'],
                $config['vhost'],
            );
            $connection->close();
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            Log::warning('Health check: rabbitmq failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function measureLatency(callable $fn): float
    {
        $start = microtime(true);
        $fn();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
