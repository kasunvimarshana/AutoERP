<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     operationId="checkSystemHealth",
     *     tags={"Health"},
     *     summary="Check system health status",
     *     description="Returns the current health status of the system including database and cache connectivity checks. This endpoint is public and does not require authentication.",
     *     @OA\Response(
     *         response=200,
     *         description="System is healthy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="healthy", description="Overall system status"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Timestamp of the health check"),
     *             @OA\Property(
     *                 property="checks",
     *                 type="object",
     *                 description="Individual component health checks",
     *                 @OA\Property(
     *                     property="database",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="message", type="string", example="Database connection successful")
     *                 ),
     *                 @OA\Property(
     *                     property="cache",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="message", type="string", example="Cache is working")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="System is unhealthy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="unhealthy"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
     *             @OA\Property(
     *                 property="checks",
     *                 type="object",
     *                 @OA\Property(
     *                     property="database",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Connection failed")
     *                 ),
     *                 @OA\Property(
     *                     property="cache",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Cache is not working properly")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $status = collect($checks)->every(fn ($check) => $check['status'] === 'ok')
            ? 'healthy'
            : 'unhealthy';

        $code = $status === 'healthy' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $code);
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');

            if ($value === true) {
                return ['status' => 'ok', 'message' => 'Cache is working'];
            }

            return ['status' => 'error', 'message' => 'Cache is not working properly'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
