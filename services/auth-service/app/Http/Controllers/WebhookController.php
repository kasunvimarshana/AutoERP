<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Repositories\BaseRepository;
use App\Infrastructure\Webhook\WebhookDispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * WebhookController
 *
 * Manages tenant webhook endpoint registration and delivery history.
 * Endpoints are registered per tenant and receive signed payloads
 * for the event types they subscribe to.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookDispatcher $dispatcher,
    ) {}

    /**
     * GET /api/webhooks
     * List all webhook endpoints for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $perPage  = (int) $request->get('per_page', 15);

        $endpoints = DB::table('webhook_endpoints')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($endpoints);
    }

    /**
     * POST /api/webhooks
     * Register a new webhook endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url'         => ['required', 'url', 'max:500'],
            'events'      => ['required', 'array', 'min:1'],
            'events.*'    => ['string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata'    => ['sometimes', 'array'],
        ]);

        $data['tenant_id'] = $request->attributes->get('tenant_id');
        $data['secret']    = bin2hex(random_bytes(32)); // cryptographically secure secret
        $data['id']        = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $data['events']    = json_encode($data['events']);
        $data['created_at']= now()->toDateTimeString();
        $data['updated_at']= now()->toDateTimeString();

        DB::table('webhook_endpoints')->insert($data);

        $data['events'] = json_decode($data['events'], true);

        return response()->json(['data' => $data], 201);
    }

    /**
     * DELETE /api/webhooks/{id}
     * Remove a webhook endpoint.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $deleted = DB::table('webhook_endpoints')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['deleted_at' => now()]);

        if (!$deleted) {
            return response()->json(['message' => 'Webhook endpoint not found.', 'error' => true], 404);
        }

        return response()->json(['message' => 'Webhook endpoint deleted successfully.']);
    }

    /**
     * POST /api/webhooks/{id}/test
     * Send a test ping to the webhook endpoint.
     */
    public function test(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $endpoint = DB::table('webhook_endpoints')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();

        if (!$endpoint) {
            return response()->json(['message' => 'Webhook endpoint not found.', 'error' => true], 404);
        }

        $success = $this->dispatcher->dispatch(
            url:       $endpoint->url,
            eventType: 'webhook.test',
            payload:   [
                'event'     => 'webhook.test',
                'tenant_id' => $tenantId,
                'message'   => 'This is a test webhook delivery.',
                'timestamp' => now()->toIso8601String(),
            ],
            secret:    $endpoint->secret,
        );

        return response()->json([
            'message' => $success ? 'Test webhook delivered successfully.' : 'Test webhook delivery failed.',
            'success' => $success,
        ]);
    }

    /**
     * GET /api/webhooks/{id}/deliveries
     * View delivery history for a webhook endpoint.
     */
    public function deliveries(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $endpoint = DB::table('webhook_endpoints')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();

        if (!$endpoint) {
            return response()->json(['message' => 'Webhook endpoint not found.', 'error' => true], 404);
        }

        $deliveries = DB::table('webhook_deliveries')
            ->where('webhook_endpoint_id', $id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json($deliveries);
    }
}
