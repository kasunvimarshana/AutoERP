<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\UseCases\ClearConfigurationCacheServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\DeleteConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\GetConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\ListConfigurationsServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\UpdateConfigurationServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationMutationData;
use Modules\Configuration\Application\DTOs\ConfigurationQueryData;
use Modules\Configuration\Presentation\Http\Requests\ListConfigurationRequest;
use Modules\Configuration\Presentation\Http\Requests\UpsertConfigurationRequest;
use Modules\Configuration\Presentation\Http\Resources\ConfigurationResource;
use Modules\Core\Application\DTO\PagedResult;

final class ConfigurationController extends Controller
{
    public function __construct(
        private readonly ListConfigurationsServiceInterface $listConfigurations,
        private readonly GetConfigurationServiceInterface $getConfiguration,
        private readonly SetConfigurationServiceInterface $setConfiguration,
        private readonly UpdateConfigurationServiceInterface $updateConfiguration,
        private readonly DeleteConfigurationServiceInterface $deleteConfiguration,
        private readonly ClearConfigurationCacheServiceInterface $clearConfigurationCache,
    ) {
    }

    public function index(ListConfigurationRequest $request): JsonResponse
    {
        $result = $this->listConfigurations->execute(ConfigurationQueryData::fromArray($request->validated()));

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return $this->errorResponse('Unexpected list response.', 500);
        }

        return response()->json([
            'data' => ConfigurationResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(string $key): JsonResponse|ConfigurationResource
    {
        $result = $this->getConfiguration->execute($key);

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 404);
        }

        return new ConfigurationResource($result->valueOrFail());
    }

    public function store(UpsertConfigurationRequest $request): JsonResponse|ConfigurationResource
    {
        $payload = $request->validated();
        $data = ConfigurationMutationData::fromArray($payload);

        $result = $this->setConfiguration->execute($data);
        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 422);
        }

        return (new ConfigurationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertConfigurationRequest $request, string $key): JsonResponse|ConfigurationResource
    {
        $payload = $request->validated();
        $payload['key'] = $key;

        $result = $this->updateConfiguration->execute($key, ConfigurationMutationData::fromArray($payload));

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 422);
        }

        return new ConfigurationResource($result->valueOrFail());
    }

    public function clearCache(): JsonResponse
    {
        $result = $this->clearConfigurationCache->execute();

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 422);
        }

        return response()->json(['message' => 'Configuration cache cleared.']);
    }

    public function destroy(string $key): JsonResponse
    {
        $result = $this->deleteConfiguration->execute($key);

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 404);
        }

        return response()->json(null, 204);
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
