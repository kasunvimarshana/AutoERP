<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Supplier\Application\Contracts\SupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Infrastructure\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierResource;

/**
 * @OA\Tag(name="Suppliers", description="Supplier management")
 */
final class SupplierController extends AuthorizedController
{
    public function __construct(private readonly SupplierServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/supplier/suppliers",
     *     tags={"Suppliers"},
     *     summary="List suppliers",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive","blocked"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of suppliers")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = array_filter(
            $request->only(['status']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $filters['tenant_id'] = $tenantId;

        return SupplierResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/supplier/suppliers",
     *     tags={"Suppliers"},
     *     summary="Create a supplier",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreSupplierRequest")),
     *     @OA\Response(response=201, description="Supplier created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = SupplierData::fromArray($request->validated());
        $supplier = $this->service->create($dto, $tenantId);

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/supplier/suppliers/{id}",
     *     tags={"Suppliers"},
     *     summary="Get a supplier by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Supplier details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new SupplierResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/supplier/suppliers/{id}",
     *     tags={"Suppliers"},
     *     summary="Update a supplier",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateSupplierRequest")),
     *     @OA\Response(response=200, description="Supplier updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $dto      = SupplierData::fromArray($request->validated());
        $supplier = $this->service->update($id, $dto);

        return (new SupplierResource($supplier))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/supplier/suppliers/{id}",
     *     tags={"Suppliers"},
     *     summary="Delete a supplier",
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
