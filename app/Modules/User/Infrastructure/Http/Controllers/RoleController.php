<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Infrastructure\Http\Requests\StoreRoleRequest;
use Modules\User\Infrastructure\Http\Requests\SyncRolePermissionsRequest;
use Modules\User\Infrastructure\Http\Resources\RoleResource;
use OpenApi\Attributes as OA;

class RoleController extends AuthorizedController
{
    public function __construct(
        protected CreateRoleServiceInterface $createService,
        protected DeleteRoleServiceInterface $deleteService,
        protected SyncRolePermissionsServiceInterface $syncPermissionsService,
        protected RoleRepositoryInterface $roleRepository
    ) {}

    #[OA\Get(
        path: '/api/roles',
        summary: 'List roles',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of roles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RoleObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);
        $repo = clone $this->roleRepository;
        if ($tenantId = $request->query('tenant_id')) {
            $repo->where('tenant_id', (int) $tenantId);
        }
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $roles = $repo->paginate($perPage, ['*'], 'page', $page);

        return response()->json(RoleResource::collection($roles));
    }

    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Get role',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role details',
                content: new OA\JsonContent(ref: '#/components/schemas/RoleObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): RoleResource
    {
        $role = $this->roleRepository->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('view', $role);

        return new RoleResource($role);
    }

    #[OA\Post(
        path: '/api/roles',
        summary: 'Create role',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',      type: 'string',  example: 'manager'),
                    new OA\Property(property: 'tenant_id', type: 'integer', nullable: true, example: 1),
                ],
            ),
        ),
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Role created',
                content: new OA\JsonContent(ref: '#/components/schemas/RoleObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreRoleRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Role::class);
        $role = $this->createService->execute($request->validated());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/roles/{id}',
        summary: 'Delete role',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleRepository->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('delete', $role);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Role deleted successfully']);
    }

    #[OA\Put(
        path: '/api/roles/{id}/permissions',
        summary: 'Sync role permissions',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permission_ids'],
                properties: [
                    new OA\Property(property: 'permission_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                ],
            ),
        ),
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role with synced permissions',
                content: new OA\JsonContent(ref: '#/components/schemas/RoleObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function syncPermissions(SyncRolePermissionsRequest $request, int $id): RoleResource
    {
        $role = $this->roleRepository->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('syncPermissions', $role);
        $updated = $this->syncPermissionsService->execute([
            'role_id' => $id,
            'permission_ids' => $request->validated()['permission_ids'],
        ]);

        return new RoleResource($updated);
    }
}
