<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'service'   => 'user-service',
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '1.0.0'),
            'env'       => config('app.env', 'production'),
        ]);
    }
}
