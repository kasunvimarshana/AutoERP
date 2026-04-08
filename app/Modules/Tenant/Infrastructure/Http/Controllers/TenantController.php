<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\TenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;

/**
 * @OA\Tag(name="Tenants", description="Tenant management endpoints")
 */
final class TenantController extends AuthorizedController
{
    public function __construct(private readonly TenantServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/tenants",
     *     tags={"Tenants"},
     *     summary="List all tenants",
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated tenant list")
     * )
     */
    public function index(): ResourceCollection
    {
        $paginated = $this->service->list();

        return TenantResource::collection($paginated);
    }

    /**
     * @OA\Post(
     *     path="/api/tenants",
     *     tags={"Tenants"},
     *     summary="Create a new tenant",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreTenantRequest")),
     *     @OA\Response(response=201, description="Tenant created")
     * )
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $dto = TenantData::fromArray($request->validated());
        $tenant = $this->service->create($dto);

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Get a tenant by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tenant details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $tenant): JsonResponse
    {
        $record = $this->service->find($tenant);

        return (new TenantResource($record))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Update a tenant",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateTenantRequest")),
     *     @OA\Response(response=200, description="Tenant updated")
     * )
     */
    public function update(UpdateTenantRequest $request, int $tenant): JsonResponse
    {
        $record = $this->service->update($tenant, $request->validated());

        return (new TenantResource($record))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Soft-delete a tenant",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(int $tenant): JsonResponse
    {
        $this->service->delete($tenant);

        return response()->json(null, 204);
    }
}
