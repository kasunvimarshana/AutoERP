<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Domain\Entities\User;
use Modules\User\Infrastructure\Http\Requests\StoreUserRequest;
use Modules\User\Infrastructure\Http\Requests\UpdatePreferencesRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\User\Infrastructure\Http\Resources\UserCollection;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use OpenApi\Attributes as OA;

class UserController extends BaseController
{
    public function __construct(
        CreateUserServiceInterface $createService,
        protected UpdateUserServiceInterface $updateService,
        protected DeleteUserServiceInterface $deleteService,
        protected AssignRoleServiceInterface $assignRoleService,
        protected UpdatePreferencesServiceInterface $updatePreferencesService
    ) {
        parent::__construct($createService, UserResource::class, UserData::class);
    }

    #[OA\Get(
        path: '/api/users',
        summary: 'List users',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'email',    in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'email')),
            new OA\Parameter(name: 'active',   in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'role',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',     in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'name:asc')),
            new OA\Parameter(name: 'include',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of users',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/UserObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): UserCollection
    {
        $this->authorize('viewAny', User::class);
        $filters = $request->only(['name', 'email', 'active', 'role']);

        if ($request->has('active')) {
            $filters['active'] = $request->boolean('active');
        }

        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $sort = $request->input('sort');
        $include = $request->input('include');

        $users = $this->service->list($filters, $perPage, $page, $sort, $include);

        return new UserCollection($users);
    }

    #[OA\Post(
        path: '/api/users',
        summary: 'Create user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'email', 'first_name', 'last_name'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer', example: 1),
                    new OA\Property(property: 'email',       type: 'string',  format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'first_name',  type: 'string',  maxLength: 255, example: 'John'),
                    new OA\Property(property: 'last_name',   type: 'string',  maxLength: 255, example: 'Doe'),
                    new OA\Property(property: 'phone',       type: 'string',  nullable: true, maxLength: 20, example: '+1-555-0100'),
                    new OA\Property(property: 'address',     type: 'object',  nullable: true),
                    new OA\Property(property: 'preferences', type: 'object',  nullable: true),
                    new OA\Property(property: 'active',      type: 'boolean', default: true),
                    new OA\Property(property: 'roles',       type: 'array',   nullable: true, items: new OA\Items(type: 'integer'), example: [1, 2]),
                ],
            ),
        ),
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'User created',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreUserRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', User::class);
        $dto = UserData::fromArray($request->validated());
        $user = $this->service->execute($dto->toArray());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Get user',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User details',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): UserResource
    {
        $user = $this->service->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    #[OA\Put(
        path: '/api/users/{id}',
        summary: 'Update user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email',      type: 'string', format: 'email'),
                    new OA\Property(property: 'first_name', type: 'string'),
                    new OA\Property(property: 'last_name',  type: 'string'),
                    new OA\Property(property: 'phone',      type: 'string', nullable: true),
                    new OA\Property(property: 'active',     type: 'boolean'),
                ],
            ),
        ),
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated user',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
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
    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user = $this->service->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('update', $user);
        $validated = $request->validated();
        $validated['id'] = $id;
        $dto = UserData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());

        return new UserResource($updated);
    }

    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Delete user',
        tags: ['Users'],
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
        $user = $this->service->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('delete', $user);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'User deleted successfully']);
    }

    #[OA\Post(
        path: '/api/users/{id}/assign-role',
        summary: 'Assign role to user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role_id'],
                properties: [
                    new OA\Property(property: 'role_id', type: 'integer', example: 2),
                ],
            ),
        ),
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role assigned',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
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
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $user = $this->service->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('assignRole', $user);
        $validated = $request->validate(['role_id' => 'required|integer|exists:roles,id']);
        $this->assignRoleService->execute(['user_id' => $id, 'role_id' => $validated['role_id']]);

        return response()->json(['message' => 'Role assigned successfully']);
    }

    #[OA\Patch(
        path: '/api/users/{id}/preferences',
        summary: 'Update user preferences',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserPreferencesObject'),
        ),
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated user with preferences',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function updatePreferences(UpdatePreferencesRequest $request, int $id): UserResource
    {
        $user = $this->service->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('updatePreferences', $user);
        $validated = $request->validated();
        $dto = UserPreferencesData::fromArray($validated);
        $updated = $this->updatePreferencesService->execute(['user_id' => $id] + $dto->toArray());

        return new UserResource($updated);
    }

    protected function getModelClass(): string
    {
        return User::class;
    }
}
