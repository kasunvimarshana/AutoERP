<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Request;
use Modules\Auth\Application\Contracts\RoleServiceInterface;
use Modules\Auth\Application\DTOs\RoleData;
use Modules\Auth\Infrastructure\Http\Requests\StoreRoleRequest;
use Modules\Auth\Infrastructure\Http\Resources\RoleResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;

/**
 * @OA\Tag(name="Roles", description="Role management endpoints")
 */
final class RoleController extends AuthorizedController
{
    public function __construct(private readonly RoleServiceInterface $roleService) {}

    /**
     * @OA\Get(
     *     path="/api/roles",
     *     tags={"Roles"},
     *     summary="List all roles",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated role list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', RoleResource::class);

        $tenantId = $request->user()?->tenant_id;
        $filters  = $tenantId ? ['tenant_id' => $tenantId] : [];

        $paginated = $this->roleService->list($filters, (int) $request->query('per_page', 15));

        return RoleResource::collection($paginated);
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     tags={"Roles"},
     *     summary="Create a new role",
     *     security={{"passport":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreRoleRequest")),
     *     @OA\Response(response=201, description="Role created")
     * )
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', RoleResource::class);

        $dto              = RoleData::fromArray($request->validated());
        $dto->tenant_id   = $request->user()?->tenant_id;

        $role = $this->roleService->create($dto);

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/roles/{id}",
     *     tags={"Roles"},
     *     summary="Get a role by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $role): JsonResponse
    {
        $this->authorize('view', RoleResource::class);

        $record = $this->roleService->find($role);

        return (new RoleResource($record))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     tags={"Roles"},
     *     summary="Update a role",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreRoleRequest")),
     *     @OA\Response(response=200, description="Role updated")
     * )
     */
    public function update(StoreRoleRequest $request, int $role): JsonResponse
    {
        $this->authorize('update', RoleResource::class);

        $record = $this->roleService->update($role, $request->validated());

        return (new RoleResource($record))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     tags={"Roles"},
     *     summary="Delete a role",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(int $role): JsonResponse
    {
        $this->authorize('delete', RoleResource::class);

        $this->roleService->delete($role);

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/roles/{id}/permissions",
     *     tags={"Roles"},
     *     summary="Sync permissions on a role",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"permission_ids"},
     *         @OA\Property(property="permission_ids", type="array", @OA\Items(type="integer"))
     *     )),
     *     @OA\Response(response=204, description="Permissions synced")
     * )
     */
    public function syncPermissions(Request $request, int $role): JsonResponse
    {
        $this->authorize('syncPermissions', RoleResource::class);

        $request->validate([
            'permission_ids'   => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        // Delegate to AuthorizationService via service binding
        app(\Modules\Auth\Application\Contracts\AuthorizationServiceInterface::class)
            ->syncPermissions($role, $request->input('permission_ids'));

        return response()->json(null, 204);
    }
}
