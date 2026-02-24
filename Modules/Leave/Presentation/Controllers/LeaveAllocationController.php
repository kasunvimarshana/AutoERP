<?php

namespace Modules\Leave\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Leave\Application\UseCases\ApproveLeaveAllocationUseCase;
use Modules\Leave\Application\UseCases\CreateLeaveAllocationUseCase;
use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;
use Modules\Leave\Presentation\Requests\ApproveLeaveAllocationRequest;
use Modules\Leave\Presentation\Requests\StoreLeaveAllocationRequest;

class LeaveAllocationController extends Controller
{
    public function __construct(
        private LeaveAllocationRepositoryInterface $allocationRepo,
        private CreateLeaveAllocationUseCase        $createUseCase,
        private ApproveLeaveAllocationUseCase       $approveUseCase,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;

        return response()->json(
            $this->allocationRepo->paginate(15, ['tenant_id' => $tenantId])
        );
    }

    public function store(StoreLeaveAllocationRequest $request): JsonResponse
    {
        $allocation = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($allocation, 201);
    }

    public function show(string $id): JsonResponse
    {
        $allocation = $this->allocationRepo->findById($id);

        if (! $allocation) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($allocation);
    }

    public function approve(ApproveLeaveAllocationRequest $request, string $id): JsonResponse
    {
        $allocation = $this->approveUseCase->execute(
            $id,
            $request->validated()['approver_id'],
        );

        return response()->json($allocation);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->allocationRepo->delete($id);

        return response()->json(null, 204);
    }
}
