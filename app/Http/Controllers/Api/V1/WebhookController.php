<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($this->webhookService->paginate($tenantId, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('webhooks.create'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'events' => 'required|array',
            'events.*' => 'string|max:255',
            'secret' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'headers' => 'nullable|array',
            'retry_count' => 'nullable|integer|min:0|max:10',
            'metadata' => 'nullable|array',
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['created_by'] = $request->user()->id;

        $webhook = $this->webhookService->create($data);

        return response()->json($webhook, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('webhooks.update'), 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:255',
            'events' => 'sometimes|array',
            'events.*' => 'string|max:255',
            'secret' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'headers' => 'nullable|array',
            'retry_count' => 'nullable|integer|min:0|max:10',
            'metadata' => 'nullable|array',
        ]);

        $webhook = $this->webhookService->update($id, $data);

        return response()->json($webhook);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('webhooks.delete'), 403);

        $this->webhookService->delete($id);

        return response()->json(null, 204);
    }

    public function deliveries(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('webhooks.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = array_merge(
            $request->only(['status', 'event_name']),
            ['webhook_id' => $id]
        );

        return response()->json($this->webhookService->paginateDeliveries($tenantId, $filters, $perPage));
    }

    public function test(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('webhooks.view'), 403);

        $this->webhookService->dispatch(
            $request->user()->tenant_id,
            'webhook.test',
            ['test' => true, 'timestamp' => now()->toIso8601String()]
        );

        return response()->json(['message' => 'Test webhook dispatched.']);
    }
}
