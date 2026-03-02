<?php

declare(strict_types=1);

namespace Modules\Integration\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Integration\Application\DTOs\RegisterWebhookDTO;
use Modules\Integration\Application\Services\IntegrationService;

/**
 * Integration controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to IntegrationService.
 *
 * @OA\Tag(name="Integration", description="Webhook management and integration log endpoints")
 */
class IntegrationController extends Controller
{
    public function __construct(private readonly IntegrationService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/integration/webhooks",
     *     tags={"Integration"},
     *     summary="List all registered webhook endpoints",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of webhook endpoints",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $webhooks = $this->service->listWebhooks();

        return ApiResponse::success($webhooks, 'Webhook endpoints retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/integration/webhooks",
     *     tags={"Integration"},
     *     summary="Register a new webhook endpoint",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","url","events"},
     *             @OA\Property(property="name", type="string", example="Order Created Hook"),
     *             @OA\Property(property="url", type="string", format="url", example="https://example.com/hook"),
     *             @OA\Property(property="events", type="array", @OA\Items(type="string"), example={"order.created","order.updated"}),
     *             @OA\Property(property="secret", type="string", nullable=true, example="mysecret"),
     *             @OA\Property(property="headers", type="object", nullable=true, example={"X-Custom-Header":"value"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Webhook endpoint registered"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'url'     => ['required', 'url', 'max:2048'],
            'events'  => ['required', 'array'],
            'secret'  => ['nullable', 'string', 'max:255'],
            'headers' => ['nullable', 'array'],
        ]);

        $dto      = RegisterWebhookDTO::fromArray($validated);
        $endpoint = $this->service->registerWebhook($dto);

        return ApiResponse::created($endpoint, 'Webhook endpoint registered.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/integration/webhooks/{id}/dispatch",
     *     tags={"Integration"},
     *     summary="Create a pending webhook delivery for an endpoint",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event_name","payload"},
     *             @OA\Property(property="event_name", type="string", example="order.created"),
     *             @OA\Property(property="payload", type="object", example={"order_id":42,"total":"199.9900"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Webhook delivery queued"),
     *     @OA\Response(response=404, description="Webhook endpoint not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function dispatch(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'payload'    => ['required', 'array'],
        ]);

        $delivery = $this->service->dispatchWebhook(
            $id,
            (string) $validated['event_name'],
            (array) $validated['payload'],
        );

        return ApiResponse::created($delivery, 'Webhook delivery queued.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/integration/logs",
     *     tags={"Integration"},
     *     summary="List integration log entries",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of integration log entries",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logs(): JsonResponse
    {
        $logs = $this->service->listIntegrationLogs();

        return ApiResponse::success($logs, 'Integration logs retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/integration/webhooks/{id}",
     *     tags={"Integration"},
     *     summary="Show a single webhook endpoint",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Webhook endpoint"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function showWebhook(int $id): JsonResponse
    {
        $endpoint = $this->service->showWebhook($id);

        return ApiResponse::success($endpoint, 'Webhook endpoint retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/integration/deliveries",
     *     tags={"Integration"},
     *     summary="List all webhook deliveries",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of webhook deliveries",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listDeliveries(): JsonResponse
    {
        $deliveries = $this->service->listDeliveries();

        return ApiResponse::success($deliveries, 'Webhook deliveries retrieved.');
    }
}
