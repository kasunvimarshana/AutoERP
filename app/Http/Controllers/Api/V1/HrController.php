<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EmploymentType;
use App\Http\Controllers\Controller;
use App\Services\HrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class HrController extends Controller
{
    public function __construct(
        private readonly HrService $hrService
    ) {}

    public function indexEmployees(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['department_id', 'status', 'search']);

        return response()->json($this->hrService->paginateEmployees($tenantId, $filters, $perPage));
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employees.create'), 403);

        $data = $request->validate([
            'employee_number' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'hire_date' => 'required|date',
            'employment_type' => ['required', new Enum(EmploymentType::class)],
            'department_id' => 'nullable|uuid|exists:departments,id',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'phone' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'manager_id' => 'nullable|uuid|exists:employees,id',
            'status' => 'nullable|string|max:255',
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        $employee = $this->hrService->createEmployee($data);

        return response()->json($employee, 201);
    }

    public function updateEmployee(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employees.update'), 403);

        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'hire_date' => 'sometimes|date',
            'employment_type' => ['sometimes', new Enum(EmploymentType::class)],
            'department_id' => 'nullable|uuid|exists:departments,id',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'phone' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'manager_id' => 'nullable|uuid|exists:employees,id',
            'status' => 'nullable|string|max:255',
        ]);

        $employee = $this->hrService->updateEmployee($id, $data);

        return response()->json($employee);
    }

    public function terminateEmployee(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employees.update'), 403);

        $data = $request->validate([
            'termination_date' => 'nullable|date',
        ]);

        $employee = $this->hrService->terminateEmployee($id, $data['termination_date'] ?? null);

        return response()->json($employee);
    }

    public function indexDepartments(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($this->hrService->paginateDepartments($tenantId, $perPage));
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.departments.create'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'parent_id' => 'nullable|uuid|exists:departments,id',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|uuid|exists:employees,id',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        $department = $this->hrService->createDepartment($data);

        return response()->json($department, 201);
    }

    public function updateDepartment(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('hr.departments.update'), 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'parent_id' => 'nullable|uuid|exists:departments,id',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|uuid|exists:employees,id',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        $department = $this->hrService->updateDepartment($id, $data);

        return response()->json($department);
    }

    public function indexLeaveRequests(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['employee_id', 'status']);

        return response()->json($this->hrService->paginateLeaveRequests($tenantId, $filters, $perPage));
    }

    public function storeLeaveRequest(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.leave-requests.create'), 403);

        $data = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'leave_type_id' => 'required|uuid|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        $leaveRequest = $this->hrService->createLeaveRequest($data);

        return response()->json($leaveRequest, 201);
    }

    public function approveLeaveRequest(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('hr.leave-requests.approve'), 403);

        $data = $request->validate([
            'approved_by' => 'required|uuid|exists:employees,id',
        ]);

        $leaveRequest = $this->hrService->approveLeaveRequest($id, $data['approved_by']);

        return response()->json($leaveRequest);
    }

    public function rejectLeaveRequest(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('hr.leave-requests.approve'), 403);

        $data = $request->validate([
            'approved_by' => 'required|uuid|exists:employees,id',
        ]);

        $leaveRequest = $this->hrService->rejectLeaveRequest($id, $data['approved_by']);

        return response()->json($leaveRequest);
    }
}
