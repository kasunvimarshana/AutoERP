<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * Health-check endpoint for liveness / readiness probes.
 */
class HealthController extends Controller
{
    /**
     * Return a simple liveness/readiness response.
     *
     * Kubernetes liveness probes, load-balancer health checks, and uptime
     * monitors can poll this endpoint to verify that the application process
     * is alive and able to serve requests.  No authentication is required.
     */
    #[OA\Get(
        path: '/api/health',
        operationId: 'healthCheck',
        summary: 'Application health check',
        description: 'Returns a simple JSON payload confirming the API process is running. No authentication required. Suitable for liveness/readiness probes.',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Application is healthy',
                content: new OA\JsonContent(
                    required: ['status'],
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function check(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
