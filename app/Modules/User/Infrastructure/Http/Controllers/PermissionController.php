<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Infrastructure\Http\Requests\StorePermissionRequest;
use Modules\User\Infrastructure\Http\Resources\PermissionResource;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    public function __construct(
        protected CreatePermissionServiceInterface $createService,
        protected DeletePermissionServiceInterface $deleteService,
        protected PermissionRepositoryInterface $permissionRepository
    ) {}

    #[OA\Get(
        path: '/api/permissions',
        summary: 'List permissions',
        tags: ['Permissions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of permissions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PermissionObject')),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);
        $repo = clone $this->permissionRepository;
        if ($tenantId = $request->query('tenant_id')) {
            $repo->where('tenant_id', (int) $tenantId);
        }
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $permissions = $repo->paginate($perPage, ['*'], 'page', $page);

        return response()->json(PermissionResource::collection($permissions));
    }

    #[OA\Get(
        path: '/api/permissions/{id}',
        summary: 'Get permission',
        tags: ['Permissions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Permission details',
                content: new OA\JsonContent(ref: '#/components/schemas/PermissionObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): PermissionResource
    {
        $permission = $this->permissionRepository->find($id);
        if (! $permission) {
            abort(404);
        }
        $this->authorize('view', $permission);

        return new PermissionResource($permission);
    }

    #[OA\Post(
        path: '/api/permissions',
        summary: 'Create permission',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',      type: 'string',  example: 'users.edit'),
                    new OA\Property(property: 'tenant_id', type: 'integer', nullable: true, example: 1),
                ],
            ),
        ),
        tags: ['Permissions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Permission created',
                content: new OA\JsonContent(ref: '#/components/schemas/PermissionObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StorePermissionRequest $request): PermissionResource
    {
        $this->authorize('create', Permission::class);
        $permission = $this->createService->execute($request->validated());

        return new PermissionResource($permission);
    }

    #[OA\Delete(
        path: '/api/permissions/{id}',
        summary: 'Delete permission',
        tags: ['Permissions'],
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
        $permission = $this->permissionRepository->find($id);
        if (! $permission) {
            abort(404);
        }
        $this->authorize('delete', $permission);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
