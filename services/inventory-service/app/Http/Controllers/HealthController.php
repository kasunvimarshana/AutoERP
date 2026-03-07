<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'ok';
        $dbError  = null;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'error';
            $dbError  = $e->getMessage();
        }

        $status = $dbStatus === 'ok' ? 200 : 503;

        return response()->json([
            'status'    => $dbStatus === 'ok' ? 'ok' : 'degraded',
            'service'   => 'inventory-service',
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '1.0.0'),
            'env'       => config('app.env', 'production'),
            'checks'    => [
                'database' => [
                    'status' => $dbStatus,
                    'error'  => $dbError,
                ],
            ],
        ], $status);
    }
}
