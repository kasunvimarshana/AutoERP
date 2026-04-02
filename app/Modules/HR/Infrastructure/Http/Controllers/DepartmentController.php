<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\DeleteDepartmentServiceInterface;
use Modules\HR\Application\Contracts\FindDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UpdateDepartmentServiceInterface;
use Modules\HR\Application\DTOs\DepartmentData;
use Modules\HR\Application\DTOs\UpdateDepartmentData;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Infrastructure\Http\Requests\StoreDepartmentRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateDepartmentRequest;
use Modules\HR\Infrastructure\Http\Resources\DepartmentCollection;
use Modules\HR\Infrastructure\Http\Resources\DepartmentResource;
use OpenApi\Attributes as OA;

class DepartmentController extends AuthorizedController
{
    public function __construct(
        protected FindDepartmentServiceInterface $findService,
        protected CreateDepartmentServiceInterface $createService,
        protected UpdateDepartmentServiceInterface $updateService,
        protected DeleteDepartmentServiceInterface $deleteService,
    ) {}

    #[OA\Get(
        path: '/api/hr/departments',
        summary: 'List departments',
        tags: ['HR - Departments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of departments'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): DepartmentCollection
    {
        $this->authorize('viewAny', Department::class);
        $filters = $request->only(['tenant_id', 'is_active', 'parent_id']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $departments = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new DepartmentCollection($departments);
    }

    #[OA\Post(
        path: '/api/hr/departments',
        summary: 'Create department',
        tags: ['HR - Departments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'name'],
            properties: [
                new OA\Property(property: 'tenant_id',   type: 'integer'),
                new OA\Property(property: 'name',        type: 'string',  maxLength: 255),
                new OA\Property(property: 'code',        type: 'string',  nullable: true, maxLength: 50),
                new OA\Property(property: 'description', type: 'string',  nullable: true),
                new OA\Property(property: 'manager_id',  type: 'integer', nullable: true),
                new OA\Property(property: 'parent_id',   type: 'integer', nullable: true),
                new OA\Property(property: 'is_active',   type: 'boolean'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Department created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);
        $dto        = DepartmentData::fromArray($request->validated());
        $department = $this->createService->execute($dto->toArray());

        return (new DepartmentResource($department))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/departments/{id}',
        summary: 'Get department',
        tags: ['HR - Departments'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Department details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): DepartmentResource
    {
        $department = $this->findService->find($id);
        if (! $department) {
            abort(404);
        }
        $this->authorize('view', $department);

        return new DepartmentResource($department);
    }

    #[OA\Put(
        path: '/api/hr/departments/{id}',
        summary: 'Update department',
        tags: ['HR - Departments'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name',        type: 'string'),
            new OA\Property(property: 'description', type: 'string',  nullable: true),
            new OA\Property(property: 'is_active',   type: 'boolean'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated department'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdateDepartmentRequest $request, int $id): DepartmentResource
    {
        $department = $this->findService->find($id);
        if (! $department) {
            abort(404);
        }
        $this->authorize('update', $department);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateDepartmentData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new DepartmentResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/departments/{id}',
        summary: 'Delete department',
        tags: ['HR - Departments'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $department = $this->findService->find($id);
        if (! $department) {
            abort(404);
        }
        $this->authorize('delete', $department);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Department deleted successfully']);
    }

    #[OA\Get(
        path: '/api/hr/departments/tree',
        summary: 'Get department tree',
        tags: ['HR - Departments'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Department tree')],
    )]
    public function tree(): JsonResponse
    {
        $this->authorize('viewAny', Department::class);
        $items = $this->findService->getTree();

        return response()->json(['data' => DepartmentResource::collection(collect($items))]);
    }
}
