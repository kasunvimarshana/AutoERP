<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InventoryClientService;
use App\Services\ProductClientService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __construct(
        private readonly InventoryClientService $inventoryClient,
        private readonly ProductClientService $productClient
    ) {}

    public function check(): JsonResponse
    {
        $checks = [];
        $allHealthy = true;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $allHealthy = false;
        }

        // Redis check
        try {
            Redis::ping();
            $checks['redis'] = ['status' => 'healthy'];
        } catch (\Throwable $e) {
            $checks['redis'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $allHealthy = false;
        }

        // Inventory service check
        try {
            $inventoryHealthy = $this->inventoryClient->healthCheck();
            $checks['inventory_service'] = $inventoryHealthy
                ? ['status' => 'healthy']
                : ['status' => 'unhealthy', 'message' => 'Inventory service returned non-200'];
            if (! $inventoryHealthy) {
                $allHealthy = false;
            }
        } catch (\Throwable $e) {
            $checks['inventory_service'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $allHealthy = false;
        }

        // Product service check
        try {
            $productHealthy = $this->productClient->healthCheck();
            $checks['product_service'] = $productHealthy
                ? ['status' => 'healthy']
                : ['status' => 'unhealthy', 'message' => 'Product service returned non-200'];
            if (! $productHealthy) {
                $allHealthy = false;
            }
        } catch (\Throwable $e) {
            $checks['product_service'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $allHealthy = false;
        }

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json([
            'status'    => $allHealthy ? 'healthy' : 'unhealthy',
            'service'   => 'order-service',
            'timestamp' => now()->toIso8601String(),
            'checks'    => $checks,
        ], $statusCode);
    }
}
