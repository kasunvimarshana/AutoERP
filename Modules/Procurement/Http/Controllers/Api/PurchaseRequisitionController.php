<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Procurement\Http\Requests\StorePurchaseRequisitionRequest;
use Modules\Procurement\Http\Requests\UpdatePurchaseRequisitionRequest;
use Modules\Procurement\Http\Resources\PurchaseRequisitionResource;
use Modules\Procurement\Services\PurchaseRequisitionService;

/**
 * Purchase Requisition API Controller
 *
 * Handles API requests for purchase requisition management.
 */
class PurchaseRequisitionController extends Controller
{
    /**
     * PurchaseRequisitionController constructor.
     */
    public function __construct(
        protected PurchaseRequisitionService $requisitionService
    ) {}

    /**
     * Display a listing of purchase requisitions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $requisitions = $this->requisitionService->getPaginated($perPage);

        return PurchaseRequisitionResource::collection($requisitions)->response();
    }

    /**
     * Store a newly created purchase requisition.
     */
    public function store(StorePurchaseRequisitionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        if (! empty($lines)) {
            $requisition = $this->requisitionService->createWithLines($data, $lines);
        } else {
            $requisition = $this->requisitionService->create($data);
        }

        return (new PurchaseRequisitionResource($requisition->load('lines')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified purchase requisition.
     */
    public function show(int $id): JsonResponse
    {
        $requisition = $this->requisitionService->findById($id, true);

        if (! $requisition) {
            return response()->json([
                'message' => 'Purchase requisition not found',
            ], 404);
        }

        return (new PurchaseRequisitionResource($requisition))->response();
    }

    /**
     * Update the specified purchase requisition.
     */
    public function update(UpdatePurchaseRequisitionRequest $request, int $id): JsonResponse
    {
        $requisition = $this->requisitionService->update($id, $request->validated());

        return (new PurchaseRequisitionResource($requisition->load('lines')))->response();
    }

    /**
     * Remove the specified purchase requisition.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->requisitionService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Purchase requisition not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Purchase requisition deleted successfully',
        ], 200);
    }

    /**
     * Approve a purchase requisition.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $approverId = $request->user()->id;
        $requisition = $this->requisitionService->approve($id, $approverId);

        return (new PurchaseRequisitionResource($requisition))->response();
    }

    /**
     * Reject a purchase requisition.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $approverId = $request->user()->id;
        $reason = $request->input('reason');
        $requisition = $this->requisitionService->reject($id, $approverId, $reason);

        return (new PurchaseRequisitionResource($requisition))->response();
    }

    /**
     * Search purchase requisitions.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $requisitions = $this->requisitionService->search($query);

        return PurchaseRequisitionResource::collection($requisitions)->response();
    }

    /**
     * Get requisitions by status.
     */
    public function byStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $requisitions = $this->requisitionService->getRequisitionsByStatus($status);

        return PurchaseRequisitionResource::collection($requisitions)->response();
    }
}
