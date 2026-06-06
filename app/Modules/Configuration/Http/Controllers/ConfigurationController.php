<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Configuration\DTOs\ConfigurationMutationData;
use Modules\Configuration\DTOs\ConfigurationQueryData;
use Modules\Configuration\Http\Requests\ListConfigurationRequest;
use Modules\Configuration\Http\Requests\ResolveConfigurationRequest;
use Modules\Configuration\Http\Requests\UpsertConfigurationRequest;
use Modules\Configuration\Http\Resources\ConfigurationResource;
use Modules\Configuration\Services\ClearConfigurationCacheService;
use Modules\Configuration\Services\DeleteConfigurationService;
use Modules\Configuration\Services\GetConfigurationService;
use Modules\Configuration\Services\IsFeatureEnabledService;
use Modules\Configuration\Services\ListConfigurationsService;
use Modules\Configuration\Services\ResolveConfigurationService;
use Modules\Configuration\Services\SetConfigurationService;
use Modules\Configuration\Services\UpdateConfigurationService;
use Modules\Core\DTOs\PagedResult;

final class ConfigurationController extends Controller
{
    public function __construct(
        private readonly ListConfigurationsService $listConfigurations,
        private readonly GetConfigurationService $getConfiguration,
        private readonly ResolveConfigurationService $resolveConfiguration,
        private readonly IsFeatureEnabledService $isFeatureEnabled,
        private readonly SetConfigurationService $setConfiguration,
        private readonly UpdateConfigurationService $updateConfiguration,
        private readonly DeleteConfigurationService $deleteConfiguration,
        private readonly ClearConfigurationCacheService $clearConfigurationCache,
    ) {}

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

    public function show(ResolveConfigurationRequest $request, string $key): JsonResponse|ConfigurationResource
    {
        $payload = $request->validated();
        $tenantId = isset($payload['tenant_id']) && is_numeric($payload['tenant_id'])
            ? (int) $payload['tenant_id']
            : null;

        $result = $this->getConfiguration->execute($key, $tenantId);

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 404);
        }

        return new ConfigurationResource($result->valueOrFail());
    }

    public function resolve(ResolveConfigurationRequest $request, string $key): JsonResponse|ConfigurationResource
    {
        $payload = $request->validated();

        $tenantId = isset($payload['tenant_id']) && is_numeric($payload['tenant_id'])
            ? (int) $payload['tenant_id']
            : null;
        $organizationUnitId = isset($payload['organization_unit_id']) && is_numeric($payload['organization_unit_id'])
            ? (int) $payload['organization_unit_id']
            : null;

        if (array_key_exists('default', $payload)) {
            $result = $this->resolveConfiguration->execute(
                $key,
                $tenantId,
                $organizationUnitId,
                $payload['default'],
            );
        } else {
            $result = $this->resolveConfiguration->execute($key, $tenantId, $organizationUnitId);
        }

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 404);
        }

        return new ConfigurationResource($result->valueOrFail());
    }

    public function featureEnabled(ResolveConfigurationRequest $request, string $key): JsonResponse
    {
        $payload = $request->validated();

        $tenantId = isset($payload['tenant_id']) && is_numeric($payload['tenant_id'])
            ? (int) $payload['tenant_id']
            : null;
        $default = isset($payload['default']) ? (bool) $payload['default'] : false;

        $result = $this->isFeatureEnabled->execute($key, $tenantId, $default);

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 422);
        }

        return response()->json($result->valueOrFail());
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
        $scope = $this->stringInput('scope');
        $tenantId = $this->intInput('tenant_id');

        $result = $this->deleteConfiguration->execute($key, $scope, $tenantId);

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail()->message, 404);
        }

        return response()->json(null, 204);
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    private function intInput(string $key): ?int
    {
        $value = request()->query($key);

        return is_numeric($value) ? (int) $value : null;
    }

    private function stringInput(string $key): ?string
    {
        $value = request()->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
