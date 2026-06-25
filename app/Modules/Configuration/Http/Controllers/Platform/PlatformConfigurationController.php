<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Controllers\Platform;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LogicException;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Http\Requests\Platform\ApplyPlatformConfigurationImportRequest;
use Modules\Configuration\Http\Requests\Platform\CreatePlatformConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\Platform\DeletePlatformConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\Platform\ListPlatformConfigurationEntriesRequest;
use Modules\Configuration\Http\Requests\Platform\ListPlatformConfigurationHistoryRequest;
use Modules\Configuration\Http\Requests\Platform\PreviewPlatformConfigurationImportRequest;
use Modules\Configuration\Http\Requests\Platform\RollbackPlatformConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\Platform\UpdatePlatformConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\Platform\ViewPlatformConfigurationRequest;
use Modules\Configuration\Http\Resources\ConfigurationDefinitionResource;
use Modules\Configuration\Http\Resources\ConfigurationEntryResource;
use Modules\Configuration\Http\Resources\ConfigurationRevisionResource;
use Modules\Configuration\Http\Resources\ResolvedConfigurationResource;
use Modules\Configuration\Services\ConfigurationEntryService;
use Modules\Configuration\Services\ConfigurationGlobalImpactService;
use Modules\Configuration\Services\ConfigurationTransferService;

final class PlatformConfigurationController extends Controller
{
    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationEntryService $entries,
        private readonly ConfigurationGlobalImpactService $impact,
        private readonly ConfigurationTransferService $transfer,
    ) {}

    public function definitions(ViewPlatformConfigurationRequest $request): JsonResponse
    {
        return response()->json([
            'data' => ConfigurationDefinitionResource::collection($this->definitions->all())->resolve($request),
        ]);
    }

    public function index(ListPlatformConfigurationEntriesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        [$scope, $tenantId, $organizationUnitId] = $this->target($request);
        $page = $this->entries->listPlatform(
            $scope,
            $tenantId,
            $organizationUnitId,
            isset($validated['search']) ? (string) $validated['search'] : null,
            isset($validated['owner']) ? (string) $validated['owner'] : null,
            $request->page(),
            $request->perPage(),
        );

        return response()->json([
            'data' => ConfigurationEntryResource::collection($page->items())->resolve($request),
            'meta' => [
                'current_page' => $page->currentPage(),
                'from' => $page->firstItem(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'to' => $page->lastItem(),
                'total' => $page->total(),
            ],
            'existing_keys' => $this->entries->existingKeysPlatform($scope, $tenantId, $organizationUnitId),
        ]);
    }

    public function show(ViewPlatformConfigurationRequest $request, string $key): ConfigurationEntryResource
    {
        [$scope, $tenantId, $organizationUnitId] = $this->target($request);

        return new ConfigurationEntryResource(
            $this->entries->exactPlatform($scope, $tenantId, $organizationUnitId, $key),
        );
    }

    public function resolved(ViewPlatformConfigurationRequest $request, int $tenant, string $key): ResolvedConfigurationResource
    {
        $organizationUnitId = $request->integer('organization_unit_id') ?: null;

        return new ResolvedConfigurationResource(
            $this->entries->resolvedPlatform($key, $tenant, $organizationUnitId),
        );
    }

    public function impact(ViewPlatformConfigurationRequest $request, string $key): JsonResponse
    {
        $impact = $this->impact->forKey($key);

        return response()->json(['data' => [
            'key' => $impact->key,
            'tenant_count' => $impact->tenantCount,
            'tenant_override_count' => $impact->tenantOverrideCount,
            'inheriting_tenant_count' => $impact->inheritingTenantCount,
        ]]);
    }

    public function export(ViewPlatformConfigurationRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->transfer->exportGlobal()]);
    }

    public function previewImport(PreviewPlatformConfigurationImportRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->transfer->previewGlobal((array) $request->validated('document'))]);
    }

    public function applyImport(ApplyPlatformConfigurationImportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(['data' => $this->transfer->applyGlobal(
            (array) $validated['document'],
            (string) $validated['confirmation_digest'],
            (string) $validated['reason'],
        )]);
    }

    public function store(CreatePlatformConfigurationEntryRequest $request): JsonResponse
    {
        [$scope, $tenantId, $organizationUnitId] = $this->target($request);
        $validated = $request->validated();
        $resource = new ConfigurationEntryResource($this->entries->createPlatform(
            $scope,
            $tenantId,
            $organizationUnitId,
            (string) $validated['key'],
            $validated['value'],
        ));

        return $resource->response()->setStatusCode(201);
    }

    public function update(UpdatePlatformConfigurationEntryRequest $request, string $key): ConfigurationEntryResource
    {
        [$scope, $tenantId, $organizationUnitId] = $this->target($request);
        $validated = $request->validated();

        return new ConfigurationEntryResource($this->entries->updatePlatform(
            $scope,
            $tenantId,
            $organizationUnitId,
            $key,
            (int) $validated['expected_version'],
            $validated['value'],
        ));
    }

    public function history(ListPlatformConfigurationHistoryRequest $request, string $key): JsonResponse
    {
        [$scope, $tenantId, $organizationUnitId] = $this->target($request);
        $page = $this->entries->historyPlatform(
            $scope,
            $tenantId,
            $organizationUnitId,
            $key,
            $request->page(),
            $request->perPage(),
        );

        return response()->json([
            'data' => ConfigurationRevisionResource::collection($page->items())->resolve($request),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function rollback(
        RollbackPlatformConfigurationEntryRequest $request,
        string $key,
    ): JsonResponse|ConfigurationEntryResource {
        [$scope, $tenantId, $organizationUnitId] = $this->target($request);
        $validated = $request->validated();
        $entry = $this->entries->rollbackPlatform(
            $scope,
            $tenantId,
            $organizationUnitId,
            $key,
            (int) $validated['revision_id'],
            (int) $validated['expected_version'],
            (string) $validated['reason'],
        );

        return $entry === null
            ? response()->json(['data' => null])
            : new ConfigurationEntryResource($entry);
    }

    public function destroy(DeletePlatformConfigurationEntryRequest $request, string $key): JsonResponse
    {
        [$scope, $tenantId, $organizationUnitId] = $this->target($request);
        $this->entries->deletePlatform(
            $scope,
            $tenantId,
            $organizationUnitId,
            $key,
            (int) $request->validated('expected_version'),
        );

        return response()->json(null, 204);
    }

    /** @return array{string, int|null, int|null} */
    private function target(Request $request): array
    {
        $scope = $request->route('scope');
        if (! is_string($scope)) {
            throw new LogicException('Configuration route scope is missing.');
        }
        $tenant = $request->route('tenant');
        $organizationUnit = $request->route('organizationUnit');

        return [
            $scope,
            is_numeric($tenant) ? (int) $tenant : null,
            is_numeric($organizationUnit) ? (int) $organizationUnit : null,
        ];
    }
}
