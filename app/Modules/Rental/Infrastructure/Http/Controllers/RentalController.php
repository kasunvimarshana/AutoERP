<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Rental\Application\Contracts\RentalServiceInterface;
use Modules\Rental\Application\DTOs\CreateRentalDTO;
use Modules\Rental\Application\DTOs\UpdateRentalDTO;
use Modules\Rental\Domain\Exceptions\InvalidRentalStatusTransitionException;
use Modules\Rental\Domain\Exceptions\RentalNotFoundException;
use Modules\Rental\Domain\ValueObjects\RentalType;
use Modules\Rental\Infrastructure\Http\Requests\CancelRentalRequest;
use Modules\Rental\Infrastructure\Http\Requests\CompleteRentalRequest;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalRequest;
use Modules\Rental\Infrastructure\Http\Requests\StartRentalRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalRequest;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $filters  = array_filter([
            'status'      => $request->query('status'),
            'vehicle_id'  => $request->query('vehicle_id') ? (int) $request->query('vehicle_id') : null,
            'customer_id' => $request->query('customer_id') ? (int) $request->query('customer_id') : null,
        ]);
        $rentals = $this->service->listByTenant($tenantId, $filters);

        return response()->json(['data' => array_map(fn ($r) => $this->toArray($r), $rentals)]);
    }

    public function store(CreateRentalRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();

        $dto = new CreateRentalDTO(
            tenantId:        $tenantId,
            orgUnitId:       isset($v['org_unit_id']) ? (int) $v['org_unit_id'] : null,
            customerId:      (int) $v['customer_id'],
            vehicleId:       (int) $v['vehicle_id'],
            driverId:        isset($v['driver_id']) ? (int) $v['driver_id'] : null,
            rentalNumber:    $v['rental_number'],
            rentalType:      RentalType::from($v['rental_type']),
            scheduledStartAt: $v['scheduled_start_at'],
            scheduledEndAt:  $v['scheduled_end_at'],
            pickupLocation:  $v['pickup_location'] ?? null,
            returnLocation:  $v['return_location'] ?? null,
            ratePerDay:      (string) $v['rate_per_day'],
            estimatedDays:   (string) $v['estimated_days'],
            depositAmount:   (string) $v['deposit_amount'],
            notes:           $v['notes'] ?? null,
            metadata:        $v['metadata'] ?? null,
        );

        $rental = $this->service->create($dto);

        return response()->json(['data' => $this->toArray($rental)], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        try {
            $rental = $this->service->getById($id, $tenantId);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($rental)]);
    }

    public function update(UpdateRentalRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();

        try {
            $dto = new UpdateRentalDTO(
                driverId:        isset($v['driver_id']) ? (int) $v['driver_id'] : null,
                pickupLocation:  $v['pickup_location'] ?? null,
                returnLocation:  $v['return_location'] ?? null,
                scheduledStartAt: $v['scheduled_start_at'] ?? null,
                scheduledEndAt:  $v['scheduled_end_at'] ?? null,
                ratePerDay:      isset($v['rate_per_day']) ? (string) $v['rate_per_day'] : null,
                estimatedDays:   isset($v['estimated_days']) ? (string) $v['estimated_days'] : null,
                depositAmount:   isset($v['deposit_amount']) ? (string) $v['deposit_amount'] : null,
                notes:           $v['notes'] ?? null,
                metadata:        $v['metadata'] ?? null,
            );
            $rental = $this->service->update($id, $tenantId, $dto);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($rental)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        try {
            $this->service->delete($id, $tenantId);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(null, 204);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        try {
            $rental = $this->service->confirm($id, $tenantId);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (InvalidRentalStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->toArray($rental)]);
    }

    public function start(StartRentalRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();
        try {
            $rental = $this->service->start(
                $id,
                $tenantId,
                $v['actual_start_at'],
                isset($v['start_odometer']) ? (string) $v['start_odometer'] : null,
            );
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (InvalidRentalStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->toArray($rental)]);
    }

    public function complete(CompleteRentalRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();
        try {
            $rental = $this->service->complete(
                $id,
                $tenantId,
                $v['actual_end_at'],
                isset($v['end_odometer']) ? (string) $v['end_odometer'] : null,
            );
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (InvalidRentalStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->toArray($rental)]);
    }

    public function cancel(CancelRentalRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();
        try {
            $rental = $this->service->cancel($id, $tenantId, $v['reason']);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (InvalidRentalStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->toArray($rental)]);
    }

    private function toArray(object $rental): array
    {
        return [
            'id'                  => $rental->id,
            'tenant_id'           => $rental->tenantId,
            'org_unit_id'         => $rental->orgUnitId,
            'customer_id'         => $rental->customerId,
            'vehicle_id'          => $rental->vehicleId,
            'driver_id'           => $rental->driverId,
            'rental_number'       => $rental->rentalNumber,
            'rental_type'         => $rental->rentalType->value,
            'status'              => $rental->status->value,
            'pickup_location'     => $rental->pickupLocation,
            'return_location'     => $rental->returnLocation,
            'scheduled_start_at'  => $rental->scheduledStartAt->format('Y-m-d H:i:s'),
            'scheduled_end_at'    => $rental->scheduledEndAt->format('Y-m-d H:i:s'),
            'actual_start_at'     => $rental->actualStartAt?->format('Y-m-d H:i:s'),
            'actual_end_at'       => $rental->actualEndAt?->format('Y-m-d H:i:s'),
            'start_odometer'      => $rental->startOdometer,
            'end_odometer'        => $rental->endOdometer,
            'rate_per_day'        => $rental->ratePerDay,
            'estimated_days'      => $rental->estimatedDays,
            'actual_days'         => $rental->actualDays,
            'subtotal'            => $rental->subtotal,
            'discount_amount'     => $rental->discountAmount,
            'tax_amount'          => $rental->taxAmount,
            'total_amount'        => $rental->totalAmount,
            'deposit_amount'      => $rental->depositAmount,
            'notes'               => $rental->notes,
            'cancelled_at'        => $rental->cancelledAt?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $rental->cancellationReason,
            'metadata'            => $rental->metadata,
            'is_active'           => $rental->isActive,
            'row_version'         => $rental->rowVersion,
        ];
    }
}
