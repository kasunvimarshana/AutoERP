<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\DTOs\InventoryRecordData;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Exceptions\InventoryIntegrityException;
use Modules\Inventory\Domain\Exceptions\InventoryRecordNotFoundException;
use Modules\Inventory\Presentation\Http\Requests\InventoryRecordRequest;
use Modules\Inventory\Presentation\Http\Resources\InventoryRecordResource;

class InventoryResourceController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return InventoryRecordResource::collection($this->inventory->list($resource, $tenant, $this->filters($request), $this->perPage($request)));
        } catch (InventoryRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(InventoryRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->inventory->create($resource, InventoryRecordData::fromArray($tenant, $request->validated()));

            return (new InventoryRecordResource($record))->response()->setStatusCode(201);
        } catch (InventoryIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (InventoryRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): InventoryRecordResource|JsonResponse
    {
        try {
            return new InventoryRecordResource($this->inventory->find($resource, $tenant, $id));
        } catch (InventoryRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(InventoryRecordRequest $request, int|string $tenant, string $resource, int|string $id): InventoryRecordResource|JsonResponse
    {
        try {
            return new InventoryRecordResource($this->inventory->update($resource, $tenant, $id, InventoryRecordData::fromArray($tenant, $request->validated())));
        } catch (InventoryIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (InventoryRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->inventory->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (InventoryIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (InventoryRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return collect($request->only([
            'organization_unit_id',
            'item_id',
            'variant_id',
            'warehouse_id',
            'location_id',
            'batch_id',
            'serial_id',
            'status',
            'direction',
            'txn_type',
            'reference_type',
            'reference_id',
            'reserved_for_type',
            'reserved_for_id',
            'condition',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(InventoryRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(InventoryIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
