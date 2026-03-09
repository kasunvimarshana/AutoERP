<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Messaging\MessageBrokerFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Health Controller - Inventory Service
 */
class HealthController extends Controller
{
    public function __construct(
        protected readonly MessageBrokerFactory $brokerFactory
    ) {}

    public function check(): JsonResponse
    {
        $checks = [
            'database'       => $this->checkDatabase(),
            'redis'          => $this->checkRedis(),
            'message_broker' => $this->checkBroker(),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'service'   => 'inventory-service',
            'status'    => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks'    => $checks,
        ], $healthy ? 200 : 503);
    }

    public function live(): JsonResponse
    {
        return response()->json(['status' => 'alive']);
    }

    public function ready(): JsonResponse
    {
        $dbOk = $this->checkDatabase()['status'] === 'ok';
        return response()->json(
            ['status' => $dbOk ? 'ready' : 'not_ready'],
            $dbOk ? 200 : 503
        );
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkBroker(): array
    {
        try {
            $ok = $this->brokerFactory->getBroker()->healthCheck();
            return ['status' => $ok ? 'ok' : 'error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
