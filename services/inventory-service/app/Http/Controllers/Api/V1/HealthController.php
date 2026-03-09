<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'broker'   => $this->checkBroker(),
        ];

        $healthy    = collect($checks)->every(fn ($c) => $c['status'] === 'ok');
        $statusCode = $healthy ? 200 : 503;

        return response()->json([
            'service'  => config('app.name', 'inventory-service'),
            'version'  => config('app.version', '1.0.0'),
            'status'   => $healthy ? 'healthy' : 'degraded',
            'checks'   => $checks,
            'timestamp'=> now()->toISOString(),
        ], $statusCode);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key   = 'health_check_' . uniqid();
            Cache::put($key, 'ping', 5);
            $value   = Cache::get($key);
            Cache::forget($key);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'ping') {
                return ['status' => 'error', 'message' => 'Cache read/write mismatch.'];
            }

            return ['status' => 'ok', 'latency_ms' => $latency, 'driver' => config('cache.default')];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkBroker(): array
    {
        try {
            $driver = config('queue.default', 'rabbitmq');

            if ($driver === 'sync' || $driver === 'database') {
                return ['status' => 'ok', 'driver' => $driver, 'message' => 'No broker required.'];
            }

            if ($driver === 'rabbitmq') {
                $host    = config('queue.connections.rabbitmq.hosts.0.host', 'rabbitmq');
                $port    = config('queue.connections.rabbitmq.hosts.0.port', 5672);
                $start   = microtime(true);
                $socket  = @fsockopen($host, (int) $port, $errno, $errstr, 3);
                $latency = round((microtime(true) - $start) * 1000, 2);

                if ($socket) {
                    fclose($socket);
                    return ['status' => 'ok', 'driver' => 'rabbitmq', 'latency_ms' => $latency];
                }

                return ['status' => 'error', 'driver' => 'rabbitmq', 'message' => "Cannot connect: {$errstr}"];
            }

            return ['status' => 'ok', 'driver' => $driver];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
