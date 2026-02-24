<?php

namespace Modules\Leave\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Leave\Application\UseCases\ApproveLeaveRequestUseCase;
use Modules\Leave\Application\UseCases\RejectLeaveRequestUseCase;
use Modules\Leave\Application\UseCases\RequestLeaveUseCase;
use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Presentation\Requests\ApproveLeaveRequestRequest;
use Modules\Leave\Presentation\Requests\RejectLeaveRequestRequest;
use Modules\Leave\Presentation\Requests\StoreLeaveRequestRequest;

class LeaveRequestController extends Controller
{
    public function __construct(
        private LeaveRequestRepositoryInterface $requestRepo,
        private RequestLeaveUseCase             $requestUseCase,
        private ApproveLeaveRequestUseCase      $approveUseCase,
        private RejectLeaveRequestUseCase       $rejectUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->requestRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = $this->requestUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($leaveRequest, 201);
    }

    public function show(string $id): JsonResponse
    {
        $leaveRequest = $this->requestRepo->findById($id);

        if (! $leaveRequest) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($leaveRequest);
    }

    public function approve(ApproveLeaveRequestRequest $request, string $id): JsonResponse
    {
        $leaveRequest = $this->approveUseCase->execute($id, $request->validated()['approver_id']);

        return response()->json($leaveRequest);
    }

    public function reject(RejectLeaveRequestRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $leaveRequest = $this->rejectUseCase->execute($id, $data['reviewer_id'], $data['reason'] ?? null);

        return response()->json($leaveRequest);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->requestRepo->delete($id);

        return response()->json(null, 204);
    }
}
