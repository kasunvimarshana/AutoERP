<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\AllocationEngineServiceInterface;
use Modules\Inventory\Application\Contracts\ManageValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\ValuationEngineServiceInterface;
use Modules\Inventory\Domain\Entities\ValuationConfig;
use Modules\Inventory\Infrastructure\Http\Requests\ListValuationConfigRequest;
use Modules\Inventory\Infrastructure\Http\Requests\ResolveValuationConfigRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreValuationConfigRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateValuationConfigRequest;
use Modules\Inventory\Infrastructure\Http\Resources\ValuationConfigResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InventoryValuationController extends AuthorizedController
{
    public function __construct(
        private readonly ManageValuationConfigServiceInterface $manageValuationConfigService,
        private readonly ValuationEngineServiceInterface $valuationEngineService,
        private readonly AllocationEngineServiceInterface $allocationEngineService,
    ) {}

    // -------------------------------------------------------------------------
    // Valuation Config CRUD
    // -------------------------------------------------------------------------

    public function index(ListValuationConfigRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ValuationConfig::class);
        $validated = $request->validated();

        $configs = $this->manageValuationConfigService->list(
            tenantId: (int) $validated['tenant_id'],
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($configs);
    }

    public function store(StoreValuationConfigRequest $request): JsonResponse
    {
        $this->authorize('create', ValuationConfig::class);

        $config = $this->manageValuationConfigService->create($request->validated());

        return (new ValuationConfigResource($config))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(ListValuationConfigRequest $request, int $config): JsonResponse
    {
        $this->authorize('view', ValuationConfig::class);
        $validated = $request->validated();

        $found = $this->manageValuationConfigService->find(
            tenantId: (int) $validated['tenant_id'],
            id: $config,
        );

        return (new ValuationConfigResource($found))->response();
    }

    public function update(UpdateValuationConfigRequest $request, int $config): JsonResponse
    {
        $this->authorize('update', ValuationConfig::class);
        $validated = $request->validated();

        $updated = $this->manageValuationConfigService->update(
            tenantId: (int) $validated['tenant_id'],
            id: $config,
            data: $validated,
        );

        return (new ValuationConfigResource($updated))->response();
    }

    public function destroy(ListValuationConfigRequest $request, int $config): JsonResponse
    {
        $this->authorize('delete', ValuationConfig::class);
        $validated = $request->validated();

        $this->manageValuationConfigService->delete(
            tenantId: (int) $validated['tenant_id'],
            id: $config,
        );

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    // -------------------------------------------------------------------------
    // Resolution endpoint
    // -------------------------------------------------------------------------

    public function resolve(ResolveValuationConfigRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ValuationConfig::class);
        $validated = $request->validated();

        $method = $this->valuationEngineService->resolveValuationMethod(
            tenantId: (int) $validated['tenant_id'],
            productId: isset($validated['product_id']) ? (int) $validated['product_id'] : null,
            warehouseId: isset($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
            orgUnitId: isset($validated['org_unit_id']) ? (int) $validated['org_unit_id'] : null,
            transactionType: $validated['transaction_type'] ?? null,
        );

        $strategy = $this->allocationEngineService->resolveAllocationStrategy(
            tenantId: (int) $validated['tenant_id'],
            productId: isset($validated['product_id']) ? (int) $validated['product_id'] : null,
            warehouseId: isset($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
            orgUnitId: isset($validated['org_unit_id']) ? (int) $validated['org_unit_id'] : null,
            transactionType: $validated['transaction_type'] ?? null,
        );

        return response()->json([
            'valuation_method' => $method,
            'allocation_strategy' => $strategy,
        ]);
    }
}
