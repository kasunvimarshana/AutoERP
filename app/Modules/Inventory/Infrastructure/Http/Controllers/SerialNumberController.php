<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\SerialNumberServiceInterface;
use Modules\Inventory\Application\DTOs\SerialNumberData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreSerialNumberRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateSerialNumberRequest;
use Modules\Inventory\Infrastructure\Http\Resources\SerialNumberResource;

/**
 * @OA\Tag(name="Inventory - Serial Numbers", description="Serial number tracking management")
 */
final class SerialNumberController extends AuthorizedController
{
    public function __construct(private readonly SerialNumberServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/inventory/serial-numbers",
     *     tags={"Inventory - Serial Numbers"},
     *     summary="List serial numbers",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="product_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated serial numbers")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = array_filter(
            $request->only(['product_id', 'status', 'location_id']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $filters['tenant_id'] = $tenantId;

        return SerialNumberResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/serial-numbers",
     *     tags={"Inventory - Serial Numbers"},
     *     summary="Register a serial number",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreSerialNumberRequest")),
     *     @OA\Response(response=201, description="Serial number created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreSerialNumberRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = SerialNumberData::fromArray($request->validated());
        $record   = $this->service->create($dto, $tenantId);

        return (new SerialNumberResource($record))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/serial-numbers/{id}",
     *     tags={"Inventory - Serial Numbers"},
     *     summary="Get a serial number by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Serial number details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new SerialNumberResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/serial-numbers/by-serial",
     *     tags={"Inventory - Serial Numbers"},
     *     summary="Look up a serial number by its value",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="serial", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="product_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Serial number found"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function findBySerial(Request $request): JsonResponse
    {
        $request->validate([
            'serial'     => ['required', 'string'],
            'product_id' => ['required', 'integer'],
        ]);

        $record = $this->service->findBySerial(
            (string) $request->query('serial'),
            (int) $request->query('product_id')
        );

        return (new SerialNumberResource($record))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/inventory/serial-numbers/{id}",
     *     tags={"Inventory - Serial Numbers"},
     *     summary="Update a serial number",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateSerialNumberRequest")),
     *     @OA\Response(response=200, description="Serial number updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateSerialNumberRequest $request, int $id): JsonResponse
    {
        $dto    = SerialNumberData::fromArray($request->validated());
        $record = $this->service->update($id, $dto);

        return (new SerialNumberResource($record))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/inventory/serial-numbers/{id}",
     *     tags={"Inventory - Serial Numbers"},
     *     summary="Delete a serial number",
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
