<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StoreEmployeeRequest;
use Modules\HR\Http\Requests\UpdateEmployeeRequest;
use Modules\HR\Http\Resources\EmployeeResource;
use Modules\HR\Services\EmployeeService;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $employees = $this->employeeService->getPaginated($perPage);

        return EmployeeResource::collection($employees)->response();
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->create($request->validated());

        return (new EmployeeResource($employee))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->findById($id);
        if (! $employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return (new EmployeeResource($employee->load(['department', 'position', 'manager'])))->response();
    }

    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        $employee = $this->employeeService->update($id, $request->validated());

        return (new EmployeeResource($employee))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->employeeService->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json(['message' => 'Employee deleted successfully'], 200);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $employees = $this->employeeService->search($query);

        return EmployeeResource::collection($employees)->response();
    }

    public function getByDepartment(int $departmentId): JsonResponse
    {
        $employees = $this->employeeService->getByDepartment($departmentId);

        return EmployeeResource::collection($employees)->response();
    }

    public function terminate(Request $request, int $id): JsonResponse
    {
        $request->validate(['termination_date' => 'required|date']);
        $employee = $this->employeeService->terminate($id, $request->all());

        return (new EmployeeResource($employee))->response();
    }
}
