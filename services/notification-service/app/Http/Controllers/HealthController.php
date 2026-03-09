<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

/**
 * Health Controller - Notification Service
 */
class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = ['redis' => $this->checkRedis()];
        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'service' => 'notification-service',
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    public function live(): JsonResponse
    {
        return response()->json(['status' => 'alive']);
    }

    public function ready(): JsonResponse
    {
        $redisOk = $this->checkRedis()['status'] === 'ok';
        return response()->json(['status' => $redisOk ? 'ready' : 'not_ready'], $redisOk ? 200 : 503);
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
}
