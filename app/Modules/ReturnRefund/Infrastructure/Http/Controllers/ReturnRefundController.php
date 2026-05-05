<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ReturnRefund\Application\Contracts\ReturnRefundServiceInterface;
use Modules\ReturnRefund\Application\DTOs\CreateReturnRefundDTO;
use Modules\ReturnRefund\Application\DTOs\UpdateReturnRefundDTO;
use Modules\ReturnRefund\Domain\Exceptions\ReturnRefundNotFoundException;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;
use Modules\ReturnRefund\Infrastructure\Http\Requests\ChangeReturnStatusRequest;
use Modules\ReturnRefund\Infrastructure\Http\Requests\CreateReturnRefundRequest;
use Modules\ReturnRefund\Infrastructure\Http\Requests\UpdateReturnRefundRequest;

class ReturnRefundController extends Controller
{
    public function __construct(
        private readonly ReturnRefundServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $filters  = $request->only(['status', 'rental_id']);

        return response()->json($this->service->listByTenant($tenantId, $filters));
    }

    public function byRental(Request $request, int $rentalId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        return response()->json($this->service->listByRental($rentalId, $tenantId));
    }

    public function store(CreateReturnRefundRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $data     = $request->validated();

        $dto = new CreateReturnRefundDTO(
            tenantId: $tenantId,
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            rentalId: (int) $data['rental_id'],
            returnNumber: $data['return_number'],
            returnedAt: new DateTimeImmutable($data['returned_at']),
            endOdometer: isset($data['end_odometer']) ? (string) $data['end_odometer'] : null,
            actualDays: isset($data['actual_days']) ? (string) $data['actual_days'] : null,
            rentalCharge: (string) $data['rental_charge'],
            extraCharges: (string) $data['extra_charges'],
            damageCharges: (string) $data['damage_charges'],
            fuelCharges: (string) $data['fuel_charges'],
            depositPaid: (string) $data['deposit_paid'],
            refundAmount: (string) $data['refund_amount'],
            refundMethod: $data['refund_method'] ?? null,
            inspectionNotes: $data['inspection_notes'] ?? null,
            notes: $data['notes'] ?? null,
            damagePhotos: $data['damage_photos'] ?? null,
            metadata: $data['metadata'] ?? null,
        );

        return response()->json($this->service->create($dto), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        try {
            return response()->json($this->service->getById($id, $tenantId));
        } catch (ReturnRefundNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateReturnRefundRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $data     = $request->validated();

        $dto = new UpdateReturnRefundDTO(
            endOdometer: isset($data['end_odometer']) ? (string) $data['end_odometer'] : null,
            actualDays: isset($data['actual_days']) ? (string) $data['actual_days'] : null,
            rentalCharge: isset($data['rental_charge']) ? (string) $data['rental_charge'] : null,
            extraCharges: isset($data['extra_charges']) ? (string) $data['extra_charges'] : null,
            damageCharges: isset($data['damage_charges']) ? (string) $data['damage_charges'] : null,
            fuelCharges: isset($data['fuel_charges']) ? (string) $data['fuel_charges'] : null,
            depositPaid: isset($data['deposit_paid']) ? (string) $data['deposit_paid'] : null,
            refundAmount: isset($data['refund_amount']) ? (string) $data['refund_amount'] : null,
            refundMethod: $data['refund_method'] ?? null,
            inspectionNotes: $data['inspection_notes'] ?? null,
            notes: $data['notes'] ?? null,
            damagePhotos: $data['damage_photos'] ?? null,
            metadata: $data['metadata'] ?? null,
        );

        try {
            return response()->json($this->service->update($id, $tenantId, $dto));
        } catch (ReturnRefundNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function changeStatus(ChangeReturnStatusRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $status   = ReturnStatus::from($request->validated()['status']);

        try {
            return response()->json($this->service->changeStatus($id, $tenantId, $status));
        } catch (ReturnRefundNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');

        try {
            $this->service->delete($id, $tenantId);

            return response()->json(null, 204);
        } catch (ReturnRefundNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
