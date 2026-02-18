<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StoreLeaveRequest;
use Modules\HR\Http\Requests\UpdateLeaveRequest;
use Modules\HR\Http\Resources\LeaveResource;
use Modules\HR\Services\LeaveService;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status');

        if ($status === 'pending') {
            $leaves = $this->leaveService->getPendingLeaves();
        } else {
            $leaves = collect();
        }

        return LeaveResource::collection($leaves)->response();
    }

    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $leave = $this->leaveService->create($request->validated());

        return (new LeaveResource($leave))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $leave = $this->leaveService->findById($id);
        if (! $leave) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

        return (new LeaveResource($leave->load(['employee', 'leaveType', 'approver'])))->response();
    }

    public function update(UpdateLeaveRequest $request, int $id): JsonResponse
    {
        $leave = $this->leaveService->update($id, $request->validated());

        return (new LeaveResource($leave))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->leaveService->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

        return response()->json(['message' => 'Leave deleted successfully'], 200);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['approver_id' => 'required|integer|exists:employees,id']);
        $leave = $this->leaveService->approve($id, $request->input('approver_id'));

        return (new LeaveResource($leave))->response();
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'approver_id' => 'required|integer|exists:employees,id',
            'reason' => 'required|string',
        ]);
        $leave = $this->leaveService->reject($id, $request->input('approver_id'), $request->input('reason'));

        return (new LeaveResource($leave))->response();
    }

    public function getBalance(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'year' => 'nullable|integer',
        ]);

        $balance = $this->leaveService->getLeaveBalance(
            $request->input('employee_id'),
            $request->input('leave_type_id'),
            $request->input('year')
        );

        return response()->json($balance);
    }

    public function getByEmployee(int $employeeId): JsonResponse
    {
        $leaves = $this->leaveService->getByEmployee($employeeId);

        return LeaveResource::collection($leaves)->response();
    }
}
