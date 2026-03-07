<?php

namespace App\Http\Controllers;

use App\Http\Resources\TenantResource;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends BaseController
{
    public function __construct(private readonly WebhookService $webhookService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->webhookService->paginate(
            perPage: (int) $request->get('per_page', 15),
            filters: $request->only(['status']),
            sortBy: 'created_at',
            sortDirection: 'desc'
        );

        return $this->paginatedResponse($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url'         => ['required', 'url', 'max:500'],
            'events'      => ['required', 'array'],
            'events.*'    => ['string'],
            'secret'      => ['nullable', 'string', 'min:16'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $webhook = $this->webhookService->registerWebhook($validated);

        return $this->createdResponse($webhook, 'Webhook registered');
    }

    public function show(int $id): JsonResponse
    {
        $webhook = $this->webhookService->findById($id);

        if (!$webhook) {
            return $this->notFoundResponse('Webhook not found');
        }

        return $this->successResponse($webhook);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'url'         => ['sometimes', 'url', 'max:500'],
            'events'      => ['sometimes', 'array'],
            'events.*'    => ['string'],
            'status'      => ['sometimes', 'in:active,inactive,disabled'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $webhook = $this->webhookService->updateWebhook($id, $validated);
        return $this->successResponse($webhook, 'Webhook updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->webhookService->deleteWebhook($id);

        if (!$deleted) {
            return $this->notFoundResponse('Webhook not found');
        }

        return $this->successResponse(null, 'Webhook deleted');
    }

    /**
     * Test-fire a webhook with a sample payload.
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $webhook = $this->webhookService->findById($id);

        if (!$webhook) {
            return $this->notFoundResponse('Webhook not found');
        }

        $payload = [
            'event'  => 'webhook.test',
            'data'   => ['message' => 'This is a test webhook delivery'],
            'timestamp' => now()->toIso8601String(),
        ];

        \App\Jobs\DeliverWebhookJob::dispatch($webhook, 'webhook.test', $payload)
            ->onQueue('webhooks');

        return $this->successResponse(null, 'Test webhook dispatched');
    }
}
