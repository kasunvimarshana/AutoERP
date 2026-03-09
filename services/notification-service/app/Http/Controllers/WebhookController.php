<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\NotificationServiceInterface;
use App\Domain\Entities\WebhookSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook Controller
 *
 * Manages webhook subscriptions and delivery history.
 */
class WebhookController extends Controller
{
    public function __construct(
        protected readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * List all webhook subscriptions for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $subscriptions = WebhookSubscription::where('tenant_id', $tenantId)->get();

        return response()->json(['success' => true, 'data' => $subscriptions]);
    }

    /**
     * Register a new webhook subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'events' => ['sometimes', 'array'],
            'events.*' => ['string'],
            'secret' => ['sometimes', 'string', 'min:16'],
        ]);

        $tenantId = $request->attributes->get('tenant_id');

        $subscription = $this->notificationService->registerWebhook(
            $tenantId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Webhook registered successfully.',
            'data' => $subscription,
        ], 201);
    }

    /**
     * Update a webhook subscription.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'url' => ['sometimes', 'url', 'max:500'],
            'events' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $subscription = WebhookSubscription::findOrFail($id);
        $subscription->update($request->only(['url', 'events', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Webhook updated.',
            'data' => $subscription,
        ]);
    }

    /**
     * Delete a webhook subscription.
     */
    public function destroy(int $id): JsonResponse
    {
        WebhookSubscription::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Webhook deleted.']);
    }

    /**
     * Receive incoming webhook from external system.
     * Validates HMAC signature before processing.
     */
    public function receive(Request $request): JsonResponse
    {
        $signature = $request->header('X-Webhook-Signature');
        $event = $request->header('X-Webhook-Event', 'unknown');
        $payload = $request->all();

        if (!$this->verifySignature($request->getContent(), $signature)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 401);
        }

        $this->notificationService->handleEvent($event, $payload);

        return response()->json(['success' => true, 'message' => 'Webhook received.']);
    }

    /**
     * Get delivery history for a subscription.
     */
    public function deliveries(int $id): JsonResponse
    {
        $subscription = WebhookSubscription::with('deliveries')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subscription->deliveries,
        ]);
    }

    private function verifySignature(string $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $secret = config('webhook.secret', '');
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
