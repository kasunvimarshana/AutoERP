<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Health-check endpoint for liveness / readiness probes.
 */
class HealthController extends AuthorizedController
{
    /**
     * Return a simple liveness/readiness response.
     *
     * Kubernetes liveness probes, load-balancer health checks, and uptime
     * monitors can poll this endpoint to verify that the application process
     * is alive and able to serve requests.  No authentication is required.
     */
    public function check(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
