<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\UoM\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Application\DTOs\UpdateUnitOfMeasureData;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Infrastructure\Http\Requests\StoreUnitOfMeasureRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateUnitOfMeasureRequest;
use Modules\UoM\Infrastructure\Http\Resources\UnitOfMeasureCollection;
use Modules\UoM\Infrastructure\Http\Resources\UnitOfMeasureResource;

class UnitOfMeasureController extends AuthorizedController
{
    public function __construct(
        protected FindUnitOfMeasureServiceInterface $findService,
        protected CreateUnitOfMeasureServiceInterface $createService,
        protected UpdateUnitOfMeasureServiceInterface $updateService,
        protected DeleteUnitOfMeasureServiceInterface $deleteService,
    ) {}

    public function index(Request $request): UnitOfMeasureCollection
    {
        $this->authorize('viewAny', UnitOfMeasure::class);
        $filters = $request->only(['name', 'code', 'uom_category_id', 'is_active', 'tenant_id', 'is_base_unit']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $units = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new UnitOfMeasureCollection($units);
    }

    public function store(StoreUnitOfMeasureRequest $request): JsonResponse
    {
        $this->authorize('create', UnitOfMeasure::class);
        $validated = $request->validated();

        $dto = UnitOfMeasureData::fromArray([
            'tenantId'      => $validated['tenant_id'],
            'uomCategoryId' => $validated['uom_category_id'],
            'name'          => $validated['name'],
            'code'          => $validated['code'],
            'symbol'        => $validated['symbol'],
            'isBaseUnit'    => $validated['is_base_unit'] ?? false,
            'factor'        => $validated['factor'] ?? 1.0,
            'description'   => $validated['description'] ?? null,
            'isActive'      => $validated['is_active'] ?? true,
        ]);

        $unit = $this->createService->execute($dto->toArray());

        return (new UnitOfMeasureResource($unit))->response()->setStatusCode(201);
    }

    public function show(int $id): UnitOfMeasureResource
    {
        $unit = $this->findService->find($id);
        if (! $unit) {
            abort(404);
        }
        $this->authorize('view', $unit);

        return new UnitOfMeasureResource($unit);
    }

    public function update(UpdateUnitOfMeasureRequest $request, int $id): UnitOfMeasureResource
    {
        $unit = $this->findService->find($id);
        if (! $unit) {
            abort(404);
        }
        $this->authorize('update', $unit);

        $validated = $request->validated();
        $payload   = ['id' => $id];

        if (array_key_exists('uom_category_id', $validated)) {
            $payload['uomCategoryId'] = $validated['uom_category_id'];
        }
        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }
        if (array_key_exists('code', $validated)) {
            $payload['code'] = $validated['code'];
        }
        if (array_key_exists('symbol', $validated)) {
            $payload['symbol'] = $validated['symbol'];
        }
        if (array_key_exists('is_base_unit', $validated)) {
            $payload['isBaseUnit'] = $validated['is_base_unit'];
        }
        if (array_key_exists('factor', $validated)) {
            $payload['factor'] = $validated['factor'];
        }
        if (array_key_exists('description', $validated)) {
            $payload['description'] = $validated['description'];
        }
        if (array_key_exists('is_active', $validated)) {
            $payload['isActive'] = $validated['is_active'];
        }

        $dto     = UpdateUnitOfMeasureData::fromArray($payload);
        $updated = $this->updateService->execute($dto->toArray() + ['id' => $id]);

        return new UnitOfMeasureResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $unit = $this->findService->find($id);
        if (! $unit) {
            abort(404);
        }
        $this->authorize('delete', $unit);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Unit of measure deleted successfully']);
    }
}
