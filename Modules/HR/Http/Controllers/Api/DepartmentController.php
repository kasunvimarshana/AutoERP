<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StoreDepartmentRequest;
use Modules\HR\Http\Requests\UpdateDepartmentRequest;
use Modules\HR\Http\Resources\DepartmentResource;
use Modules\HR\Http\Resources\EmployeeResource;
use Modules\HR\Repositories\Contracts\DepartmentRepositoryInterface;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository,
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $departments = $this->departmentRepository->paginate($perPage);

        return DepartmentResource::collection($departments)->response();
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departmentRepository->create($request->validated());

        return (new DepartmentResource($department))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $department = $this->departmentRepository->find($id);
        if (! $department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        return (new DepartmentResource($department->load(['parent', 'manager', 'children'])))->response();
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $department = $this->departmentRepository->update($id, $request->validated());

        return (new DepartmentResource($department))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->departmentRepository->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        return response()->json(['message' => 'Department deleted successfully'], 200);
    }

    public function tree(): JsonResponse
    {
        $tree = $this->departmentRepository->getTree();

        return DepartmentResource::collection($tree)->response();
    }

    public function employees(int $id): JsonResponse
    {
        $employees = $this->employeeRepository->getByDepartment($id);

        return EmployeeResource::collection($employees)->response();
    }
}
