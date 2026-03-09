<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller
 */
class HealthController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'inventory-service',
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];
        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');
        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'service' => 'inventory-service',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'hc_' . time();
            Cache::put($key, true, 5);
            $ok = Cache::get($key) === true;
            Cache::forget($key);
            return ['status' => $ok ? 'ok' : 'error'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
