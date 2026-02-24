<?php

namespace Modules\Leave\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Leave\Application\UseCases\CreateLeaveTypeUseCase;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;
use Modules\Leave\Presentation\Requests\StoreLeaveTypeRequest;

class LeaveTypeController extends Controller
{
    public function __construct(
        private LeaveTypeRepositoryInterface $leaveTypeRepo,
        private CreateLeaveTypeUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->leaveTypeRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $leaveType = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($leaveType, 201);
    }

    public function show(string $id): JsonResponse
    {
        $leaveType = $this->leaveTypeRepo->findById($id);

        if (! $leaveType) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($leaveType);
    }

    public function update(StoreLeaveTypeRequest $request, string $id): JsonResponse
    {
        $leaveType = $this->leaveTypeRepo->update($id, $request->validated());

        return response()->json($leaveType);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->leaveTypeRepo->delete($id);

        return response()->json(null, 204);
    }
}
