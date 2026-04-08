<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\HR\Application\Contracts\FindEmployeeServiceInterface;
use Modules\HR\Application\Contracts\LinkEmployeeToUserServiceInterface;
use Modules\HR\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\HR\Application\DTOs\EmployeeData;
use Modules\HR\Application\DTOs\UpdateEmployeeData;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Infrastructure\Http\Requests\LinkEmployeeToUserRequest;
use Modules\HR\Infrastructure\Http\Requests\StoreEmployeeRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateEmployeeRequest;
use Modules\HR\Infrastructure\Http\Resources\EmployeeCollection;
use Modules\HR\Infrastructure\Http\Resources\EmployeeResource;
use OpenApi\Attributes as OA;

class EmployeeController extends AuthorizedController
{
    public function __construct(
        protected FindEmployeeServiceInterface $findService,
        protected CreateEmployeeServiceInterface $createService,
        protected UpdateEmployeeServiceInterface $updateService,
        protected DeleteEmployeeServiceInterface $deleteService,
        protected LinkEmployeeToUserServiceInterface $linkUserService,
    ) {}

    #[OA\Get(
        path: '/api/hr/employees',
        summary: 'List employees',
        tags: ['HR - Employees'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'department_id',   in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'position_id',     in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status',          in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'employment_type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active',       in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page',        in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',            in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',            in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include',         in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of employees'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): EmployeeCollection
    {
        $this->authorize('viewAny', Employee::class);
        $filters = $request->only(['department_id', 'position_id', 'status', 'employment_type', 'is_active']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $employees = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new EmployeeCollection($employees);
    }

    #[OA\Post(
        path: '/api/hr/employees',
        summary: 'Create employee',
        tags: ['HR - Employees'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'first_name', 'last_name', 'email', 'employee_number', 'hire_date', 'employment_type'],
            properties: [
                new OA\Property(property: 'tenant_id',       type: 'integer'),
                new OA\Property(property: 'first_name',      type: 'string',  maxLength: 100),
                new OA\Property(property: 'last_name',       type: 'string',  maxLength: 100),
                new OA\Property(property: 'email',           type: 'string',  format: 'email'),
                new OA\Property(property: 'phone',           type: 'string',  nullable: true),
                new OA\Property(property: 'employee_number', type: 'string',  maxLength: 50),
                new OA\Property(property: 'hire_date',       type: 'string',  format: 'date'),
                new OA\Property(property: 'employment_type', type: 'string',  enum: ['full_time', 'part_time', 'contract', 'intern']),
                new OA\Property(property: 'department_id',   type: 'integer', nullable: true),
                new OA\Property(property: 'position_id',     type: 'integer', nullable: true),
                new OA\Property(property: 'salary',          type: 'number',  nullable: true),
                new OA\Property(property: 'currency',        type: 'string',  default: 'USD'),
                new OA\Property(property: 'is_active',       type: 'boolean'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Employee created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);
        $dto      = EmployeeData::fromArray($request->validated());
        $employee = $this->createService->execute($dto->toArray());

        return (new EmployeeResource($employee))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/employees/{id}',
        summary: 'Get employee',
        tags: ['HR - Employees'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Employee details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): EmployeeResource
    {
        $employee = $this->findService->find($id);
        if (! $employee) {
            abort(404);
        }
        $this->authorize('view', $employee);

        return new EmployeeResource($employee);
    }

    #[OA\Put(
        path: '/api/hr/employees/{id}',
        summary: 'Update employee',
        tags: ['HR - Employees'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name',  type: 'string'),
            new OA\Property(property: 'status',     type: 'string'),
            new OA\Property(property: 'is_active',  type: 'boolean'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated employee'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdateEmployeeRequest $request, int $id): EmployeeResource
    {
        $employee = $this->findService->find($id);
        if (! $employee) {
            abort(404);
        }
        $this->authorize('update', $employee);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateEmployeeData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new EmployeeResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/employees/{id}',
        summary: 'Delete employee',
        tags: ['HR - Employees'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $employee = $this->findService->find($id);
        if (! $employee) {
            abort(404);
        }
        $this->authorize('delete', $employee);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Employee deleted successfully']);
    }

    #[OA\Get(
        path: '/api/hr/employees/by-department/{departmentId}',
        summary: 'Get employees by department',
        tags: ['HR - Employees'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Employees in the department')],
    )]
    public function byDepartment(int $departmentId): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);
        $items = $this->findService->getByDepartment($departmentId);

        return response()->json(['data' => EmployeeResource::collection(collect($items))]);
    }

    public function linkUser(LinkEmployeeToUserRequest $request, int $id): JsonResponse
    {
        $employee = $this->findService->find($id);
        if (! $employee) {
            abort(404);
        }
        $this->authorize('update', $employee);
        $updated = $this->linkUserService->execute(['id' => $id, 'user_id' => $request->validated()['user_id']]);

        return (new EmployeeResource($updated))->response();
    }
}
