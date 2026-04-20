<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Infrastructure\Http\Requests\ListWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseCollection;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WarehouseController extends AuthorizedController
{
    public function __construct(
        private readonly CreateWarehouseServiceInterface $createWarehouseService,
        private readonly UpdateWarehouseServiceInterface $updateWarehouseService,
        private readonly DeleteWarehouseServiceInterface $deleteWarehouseService,
        private readonly FindWarehouseServiceInterface $findWarehouseService,
    ) {}

    public function index(ListWarehouseRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'],
            'org_unit_id' => $validated['org_unit_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'code' => $validated['code'] ?? null,
            'type' => $validated['type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'is_default' => $validated['is_default'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $warehouses = $this->findWarehouseService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new WarehouseCollection($warehouses))->response();
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = $this->createWarehouseService->execute($request->validated());

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $warehouse): WarehouseResource
    {
        $foundWarehouse = $this->findWarehouseOrFail($warehouse, $this->resolveTenantId($request));
        $this->authorize('view', $foundWarehouse);

        return new WarehouseResource($foundWarehouse);
    }

    public function update(UpdateWarehouseRequest $request, int $warehouse): WarehouseResource
    {
        $foundWarehouse = $this->findWarehouseOrFail($warehouse, (int) $request->validated('tenant_id'));
        $this->authorize('update', $foundWarehouse);

        $payload = $request->validated();
        $payload['id'] = $warehouse;

        return new WarehouseResource($this->updateWarehouseService->execute($payload));
    }

    public function destroy(Request $request, int $warehouse): JsonResponse
    {
        $foundWarehouse = $this->findWarehouseOrFail($warehouse, $this->resolveTenantId($request));
        $this->authorize('delete', $foundWarehouse);

        $this->deleteWarehouseService->execute(['id' => $warehouse]);

        return Response::json(['message' => 'Warehouse deleted successfully']);
    }

    private function findWarehouseOrFail(int $warehouseId, int $tenantId): Warehouse
    {
        $warehouse = $this->findWarehouseService->find($warehouseId);

        if (! $warehouse instanceof Warehouse || $warehouse->getTenantId() !== $tenantId) {
            throw new NotFoundHttpException('Warehouse not found.');
        }

        return $warehouse;
    }

    private function resolveTenantId(Request $request): int
    {
        return (int) ($request->user()?->tenant_id ?? $request->header('X-Tenant-ID', '0'));
    }
}
