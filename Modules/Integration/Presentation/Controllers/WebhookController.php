<?php

namespace Modules\Integration\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Integration\Application\UseCases\CreateWebhookUseCase;
use Modules\Integration\Domain\Contracts\WebhookRepositoryInterface;
use Modules\Integration\Presentation\Requests\StoreWebhookRequest;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookRepositoryInterface $webhookRepo,
        private CreateWebhookUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->webhookRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = $this->createUseCase->execute(array_merge(
            $request->validated(),
            ['tenant_id' => auth()->user()?->tenant_id]
        ));

        return response()->json($webhook, 201);
    }

    public function show(string $id): JsonResponse
    {
        $webhook = $this->webhookRepo->findById($id);

        if (! $webhook) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($webhook);
    }

    public function update(StoreWebhookRequest $request, string $id): JsonResponse
    {
        $webhook = $this->webhookRepo->update($id, $request->validated());

        return response()->json($webhook);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->webhookRepo->delete($id);

        return response()->json(null, 204);
    }
}
