<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Infrastructure\Http\Requests\ListWarehouseLocationRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseLocationRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateWarehouseLocationRequest;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseLocationCollection;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseLocationResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WarehouseLocationController extends AuthorizedController
{
    public function __construct(
        private readonly CreateWarehouseLocationServiceInterface $createWarehouseLocationService,
        private readonly UpdateWarehouseLocationServiceInterface $updateWarehouseLocationService,
        private readonly DeleteWarehouseLocationServiceInterface $deleteWarehouseLocationService,
        private readonly FindWarehouseLocationServiceInterface $findWarehouseLocationService,
        private readonly FindWarehouseServiceInterface $findWarehouseService,
    ) {}

    public function index(ListWarehouseLocationRequest $request, int $warehouse): JsonResponse
    {
        $this->authorize('viewAny', WarehouseLocation::class);
        $validated = $request->validated();
        $this->findWarehouseOrFail($warehouse, (int) $validated['tenant_id']);

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'],
            'warehouse_id' => $warehouse,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'code' => $validated['code'] ?? null,
            'type' => $validated['type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'is_pickable' => $validated['is_pickable'] ?? null,
            'is_receivable' => $validated['is_receivable'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $locations = $this->findWarehouseLocationService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? 'path:asc',
        );

        return (new WarehouseLocationCollection($locations))->response();
    }

    public function store(StoreWarehouseLocationRequest $request, int $warehouse): JsonResponse
    {
        $this->authorize('create', WarehouseLocation::class);
        $validated = $request->validated();

        $this->findWarehouseOrFail($warehouse, (int) $validated['tenant_id']);

        $payload = $validated;
        $payload['warehouse_id'] = $warehouse;

        $location = $this->createWarehouseLocationService->execute($payload);

        return (new WarehouseLocationResource($location))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $warehouse, int $location): WarehouseLocationResource
    {
        $tenantId = $this->resolveTenantId($request);
        $this->findWarehouseOrFail($warehouse, $tenantId);

        $foundLocation = $this->findLocationOrFail($location, $warehouse, $tenantId);
        $this->authorize('view', $foundLocation);

        return new WarehouseLocationResource($foundLocation);
    }

    public function update(UpdateWarehouseLocationRequest $request, int $warehouse, int $location): WarehouseLocationResource
    {
        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];
        $this->findWarehouseOrFail($warehouse, $tenantId);

        $foundLocation = $this->findLocationOrFail($location, $warehouse, $tenantId);
        $this->authorize('update', $foundLocation);

        $payload = $validated;
        $payload['id'] = $location;
        $payload['warehouse_id'] = $warehouse;

        return new WarehouseLocationResource($this->updateWarehouseLocationService->execute($payload));
    }

    public function destroy(Request $request, int $warehouse, int $location): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $this->findWarehouseOrFail($warehouse, $tenantId);

        $foundLocation = $this->findLocationOrFail($location, $warehouse, $tenantId);
        $this->authorize('delete', $foundLocation);

        $this->deleteWarehouseLocationService->execute(['id' => $location]);

        return Response::json(['message' => 'Warehouse location deleted successfully']);
    }

    private function findWarehouseOrFail(int $warehouseId, int $tenantId): Warehouse
    {
        $warehouse = $this->findWarehouseService->find($warehouseId);

        if (! $warehouse instanceof Warehouse || $warehouse->getTenantId() !== $tenantId) {
            throw new NotFoundHttpException('Warehouse not found.');
        }

        return $warehouse;
    }

    private function findLocationOrFail(int $locationId, int $warehouseId, int $tenantId): WarehouseLocation
    {
        $location = $this->findWarehouseLocationService->find($locationId);

        if (! $location instanceof WarehouseLocation || $location->getTenantId() !== $tenantId || $location->getWarehouseId() !== $warehouseId) {
            throw new NotFoundHttpException('Warehouse location not found.');
        }

        return $location;
    }

    private function resolveTenantId(Request $request): int
    {
        return (int) ($request->user()?->tenant_id ?? $request->header('X-Tenant-ID', '0'));
    }
}
