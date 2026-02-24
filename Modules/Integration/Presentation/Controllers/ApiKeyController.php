<?php

namespace Modules\Integration\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Integration\Application\UseCases\CreateApiKeyUseCase;
use Modules\Integration\Domain\Contracts\ApiKeyRepositoryInterface;
use Modules\Integration\Presentation\Requests\StoreApiKeyRequest;

class ApiKeyController extends Controller
{
    public function __construct(
        private ApiKeyRepositoryInterface $apiKeyRepo,
        private CreateApiKeyUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->apiKeyRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $apiKey = $this->createUseCase->execute(array_merge(
            $request->validated(),
            ['tenant_id' => auth()->user()?->tenant_id]
        ));

        return response()->json($apiKey, 201);
    }

    public function show(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyRepo->findById($id);

        if (! $apiKey) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($apiKey);
    }

    public function revoke(string $id): JsonResponse
    {
        $apiKey = $this->apiKeyRepo->revoke($id);

        return response()->json($apiKey);
    }
}
