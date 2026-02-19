<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Workflow\Http\Requests\ApprovalDecisionRequest;
use Modules\Workflow\Models\Approval;
use Modules\Workflow\Repositories\ApprovalRepository;
use Modules\Workflow\Resources\ApprovalResource;
use Modules\Workflow\Services\ApprovalService;

class ApprovalController extends Controller
{
    public function __construct(
        private ApprovalRepository $approvalRepository,
        private ApprovalService $approvalService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Approval::class);

        $filters = $request->only(['workflow_instance_id', 'approver_id', 'status']);
        $approvals = $this->approvalRepository->paginate($filters, $request->get('per_page', 15));

        return ApiResponse::paginated(
            $approvals->setCollection(
                $approvals->getCollection()->map(fn ($approval) => new ApprovalResource($approval))
            ),
            'Approvals retrieved successfully'
        );
    }

    public function show(Approval $approval): JsonResponse
    {
        $this->authorize('view', $approval);

        $approval->load(['instance.workflow', 'step', 'approver', 'delegatedTo']);

        return ApiResponse::success(
            new ApprovalResource($approval),
            'Approval retrieved successfully'
        );
    }

    public function pending(Request $request): JsonResponse
    {
        $approvals = $this->approvalService->getPendingApprovals($request->user()->id);

        return ApiResponse::success(
            ApprovalResource::collection($approvals),
            'Pending approvals retrieved successfully'
        );
    }

    public function respond(ApprovalDecisionRequest $request, Approval $approval): JsonResponse
    {
        $this->authorize('respond', $approval);

        $decision = $request->input('decision');
        $data = [
            'comments' => $request->input('comments'),
            'decision_data' => $request->input('decision_data', []),
        ];

        if ($decision === 'approve') {
            $this->approvalService->approve($approval, $data);
            $message = 'Approval approved successfully';
        } else {
            $this->approvalService->reject($approval, $data);
            $message = 'Approval rejected successfully';
        }

        return ApiResponse::success(
            new ApprovalResource($approval->fresh()),
            $message
        );
    }

    public function delegate(Request $request, Approval $approval): JsonResponse
    {
        $this->authorize('delegate', $approval);

        $request->validate([
            'delegate_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $this->approvalService->delegate($approval, $request->input('delegate_to'));

        return ApiResponse::success(
            new ApprovalResource($approval->fresh()),
            'Approval delegated successfully'
        );
    }
}
