<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Invoice\Requests\StoreCommissionRequest;
use Modules\Invoice\Resources\DriverCommissionResource;
use Modules\Invoice\Services\DriverCommissionService;

/**
 * DriverCommission Controller
 *
 * Handles HTTP requests for DriverCommission operations
 */
class DriverCommissionController extends Controller
{
    /**
     * DriverCommissionController constructor
     */
    public function __construct(
        private readonly DriverCommissionService $commissionService
    ) {}

    /**
     * Display a listing of commissions
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'driver_id' => $request->input('driver_id'),
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        $commissions = $this->commissionService->search($filters);

        return $this->successResponse(
            DriverCommissionResource::collection($commissions),
            __('invoice::messages.commissions_retrieved')
        );
    }

    /**
     * Calculate and store a new commission
     */
    public function store(StoreCommissionRequest $request): JsonResponse
    {
        $commission = $this->commissionService->calculateCommission($request->validated());

        return $this->createdResponse(
            new DriverCommissionResource($commission),
            __('invoice::messages.commission_calculated')
        );
    }

    /**
     * Display the specified commission
     */
    public function show(int $id): JsonResponse
    {
        $commission = $this->commissionService->getWithRelations($id);

        return $this->successResponse(
            new DriverCommissionResource($commission),
            __('invoice::messages.commission_retrieved')
        );
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'approved_by' => ['required', 'integer', 'exists:users,id'],
        ]);

        $commission = $this->commissionService->markAsPaid($id, $request->input('approved_by'));

        return $this->successResponse(
            new DriverCommissionResource($commission),
            __('invoice::messages.commission_paid')
        );
    }

    /**
     * Get commissions for a specific driver
     */
    public function byDriver(int $driverId): JsonResponse
    {
        $commissions = $this->commissionService->getForDriver($driverId);

        return $this->successResponse(
            DriverCommissionResource::collection($commissions),
            __('invoice::messages.driver_commissions_retrieved')
        );
    }

    /**
     * Get pending commissions
     */
    public function pending(): JsonResponse
    {
        $commissions = $this->commissionService->getPending();

        return $this->successResponse(
            DriverCommissionResource::collection($commissions),
            __('invoice::messages.pending_commissions_retrieved')
        );
    }
}
