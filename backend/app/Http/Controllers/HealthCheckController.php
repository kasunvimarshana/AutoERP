<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __construct(
        private readonly HealthCheckService $healthCheckService,
    ) {}

    public function index(): JsonResponse
    {
        $result     = $this->healthCheckService->check();
        $statusCode = 200;

        foreach ($result['services'] as $service) {
            if ($service['status'] !== 'ok') {
                $statusCode = 503;
                break;
            }
        }

        return response()->json($result, $statusCode);
    }

    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'message' => 'pong']);
    }
}
