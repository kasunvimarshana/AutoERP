<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StoreLeaveTypeRequest;
use Modules\HR\Http\Requests\UpdateLeaveTypeRequest;
use Modules\HR\Http\Resources\LeaveTypeResource;
use Modules\HR\Repositories\Contracts\LeaveTypeRepositoryInterface;

class LeaveTypeController extends Controller
{
    public function __construct(
        protected LeaveTypeRepositoryInterface $leaveTypeRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $leaveTypes = $this->leaveTypeRepository->paginate($perPage);

        return LeaveTypeResource::collection($leaveTypes)->response();
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $leaveType = $this->leaveTypeRepository->create($request->validated());

        return (new LeaveTypeResource($leaveType))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $leaveType = $this->leaveTypeRepository->find($id);
        if (! $leaveType) {
            return response()->json(['message' => 'Leave type not found'], 404);
        }

        return (new LeaveTypeResource($leaveType))->response();
    }

    public function update(UpdateLeaveTypeRequest $request, int $id): JsonResponse
    {
        $leaveType = $this->leaveTypeRepository->update($id, $request->validated());

        return (new LeaveTypeResource($leaveType))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->leaveTypeRepository->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Leave type not found'], 404);
        }

        return response()->json(['message' => 'Leave type deleted successfully'], 200);
    }

    public function list(): JsonResponse
    {
        $leaveTypes = $this->leaveTypeRepository->getActiveLeaveTypes();

        return LeaveTypeResource::collection($leaveTypes)->response();
    }
}
