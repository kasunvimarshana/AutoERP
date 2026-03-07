<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Infrastructure\Webhook\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook management controller.
 *
 * Also handles incoming webhook event verification.
 */
final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {}

    /** GET /api/webhooks */
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.manager')->getCurrentTenantId();

        return response()->json([
            'webhooks' => $this->webhookService->getForTenant($tenantId),
        ]);
    }

    /** POST /api/webhooks */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'url'           => ['required', 'url', 'max:500'],
            'events'        => ['required', 'array', 'min:1'],
            'events.*'      => ['required', 'string'],
            'secret'        => ['sometimes', 'string', 'min:16'],
            'max_retries'   => ['sometimes', 'integer', 'between:0,10'],
        ]);

        $tenantId = app('tenant.manager')->getCurrentTenantId();

        $webhook = $this->webhookService->register(
            $tenantId,
            $request->string('url')->toString(),
            $request->input('events'),
            $request->only(['secret', 'max_retries', 'timeout', 'headers'])
        );

        return response()->json(['webhook' => $webhook], 201);
    }

    /** DELETE /api/webhooks/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->webhookService->delete($id);

        return response()->json(['message' => 'Webhook deleted.']);
    }

    /**
     * POST /api/webhooks/receive
     *
     * Receives and verifies an incoming webhook from an external system.
     */
    public function receive(Request $request): JsonResponse
    {
        $signature = $request->header('X-Webhook-Signature', '');
        $secret    = config('app.webhook_receive_secret', '');

        if (!$this->webhookService->verifySignature($request->getContent(), $signature, $secret)) {
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        // Dispatch an internal event based on the incoming event type.
        $event   = $request->input('event', 'unknown');
        $payload = $request->input('payload', []);

        event("webhook.received.{$event}", $payload);

        return response()->json(['message' => 'Webhook received.']);
    }
}
