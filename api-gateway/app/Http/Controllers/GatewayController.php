<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\GatewayProxyInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Single controller responsible for proxying all incoming
 * requests to the appropriate downstream microservice.
 *
 * Route parameters carry the service name; the controller
 * maps them to the correct service URL from config.
 */
final class GatewayController extends Controller
{
    /** @var array<string, string> Maps service slug → env config key */
    private const SERVICE_MAP = [
        'auth'         => 'services.auth.url',
        'inventory'    => 'services.inventory.url',
        'orders'       => 'services.orders.url',
        'notifications'=> 'services.notifications.url',
    ];

    public function __construct(
        private readonly GatewayProxyInterface $proxy,
    ) {}

    /**
     * Proxy a request to the resolved downstream service.
     *
     * Route: /api/{service}/{path?}
     */
    public function proxy(Request $request, string $service): Response
    {
        $configKey = self::SERVICE_MAP[$service] ?? null;

        if (!$configKey) {
            return response(
                json_encode(['message' => "Unknown service: {$service}"]),
                404
            )->header('Content-Type', 'application/json');
        }

        $serviceUrl = config($configKey);

        if (!$serviceUrl) {
            return response(
                json_encode(['message' => "Service {$service} is not configured."]),
                503
            )->header('Content-Type', 'application/json');
        }

        return $this->proxy->forward($serviceUrl, $request);
    }
}
