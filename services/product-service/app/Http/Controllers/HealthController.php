<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = $this->checkDatabase();

        return response()->json([
            'status'    => $dbStatus === 'ok' ? 'ok' : 'degraded',
            'service'   => 'product-service',
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '1.0.0'),
            'env'       => config('app.env', 'production'),
            'checks'    => [
                'database' => $dbStatus,
            ],
        ], $dbStatus === 'ok' ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
