<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\BatchLotServiceInterface;
use Modules\Inventory\Application\DTOs\BatchLotData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreBatchLotRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateBatchLotRequest;
use Modules\Inventory\Infrastructure\Http\Resources\BatchLotResource;

/**
 * @OA\Tag(name="Inventory - Batch/Lots", description="Batch and Lot tracking management")
 */
final class BatchLotController extends AuthorizedController
{
    public function __construct(private readonly BatchLotServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/inventory/batch-lots",
     *     tags={"Inventory - Batch/Lots"},
     *     summary="List batch/lots",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="product_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated batch/lots")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = array_filter(
            $request->only(['product_id']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $filters['tenant_id'] = $tenantId;

        return BatchLotResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/batch-lots",
     *     tags={"Inventory - Batch/Lots"},
     *     summary="Create a batch/lot",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreBatchLotRequest")),
     *     @OA\Response(response=201, description="Batch/lot created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreBatchLotRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = BatchLotData::fromArray($request->validated());
        $record   = $this->service->create($dto, $tenantId);

        return (new BatchLotResource($record))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/batch-lots/{id}",
     *     tags={"Inventory - Batch/Lots"},
     *     summary="Get a batch/lot by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Batch/lot details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new BatchLotResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/inventory/batch-lots/{id}",
     *     tags={"Inventory - Batch/Lots"},
     *     summary="Update a batch/lot",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateBatchLotRequest")),
     *     @OA\Response(response=200, description="Batch/lot updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateBatchLotRequest $request, int $id): JsonResponse
    {
        $dto    = BatchLotData::fromArray($request->validated());
        $record = $this->service->update($id, $dto);

        return (new BatchLotResource($record))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/inventory/batch-lots/{id}",
     *     tags={"Inventory - Batch/Lots"},
     *     summary="Delete a batch/lot",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
