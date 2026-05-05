<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reservation\Application\Contracts\ReservationServiceInterface;
use Modules\Reservation\Application\DTOs\CreateReservationDTO;
use Modules\Reservation\Domain\Entities\Reservation;
use Modules\Reservation\Domain\Exceptions\ReservationNotFoundException;
use Modules\Reservation\Infrastructure\Http\Requests\ChangeReservationStatusRequest;
use Modules\Reservation\Infrastructure\Http\Requests\CreateReservationRequest;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $orgUnitId = (string) $request->query('org_unit_id', $tenantId);

        $reservations = $this->service->listByTenant($tenantId, $orgUnitId);

        return response()->json(['data' => array_map(fn (Reservation $reservation): array => $this->transform($reservation), $reservations)]);
    }

    public function byVehicle(Request $request, string $vehicleId): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');

        $reservations = $this->service->listByVehicle($tenantId, $vehicleId);

        return response()->json(['data' => array_map(fn (Reservation $reservation): array => $this->transform($reservation), $reservations)]);
    }

    public function store(CreateReservationRequest $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $validated = $request->validated();

        $dto = new CreateReservationDTO(
            tenantId: $tenantId,
            orgUnitId: (string) ($validated['org_unit_id'] ?? $tenantId),
            reservationNumber: $validated['reservation_number'],
            vehicleId: $validated['vehicle_id'],
            customerId: $validated['customer_id'],
            reservedFrom: new DateTimeImmutable($validated['reserved_from']),
            reservedTo: new DateTimeImmutable($validated['reserved_to']),
            estimatedAmount: isset($validated['estimated_amount'])
                ? number_format((float) $validated['estimated_amount'], 6, '.', '')
                : '0.000000',
            currency: strtoupper((string) ($validated['currency'] ?? 'USD')),
            notes: $validated['notes'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );

        $reservation = $this->service->create($dto);

        return response()->json(['data' => $this->transform($reservation)], 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $reservation = $this->service->getById($id);

            return response()->json(['data' => $this->transform($reservation)]);
        } catch (ReservationNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function changeStatus(ChangeReservationStatusRequest $request, string $id): JsonResponse
    {
        try {
            $reservation = $this->service->updateStatus($id, (string) $request->validated('status'));

            return response()->json(['data' => $this->transform($reservation)]);
        } catch (ReservationNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(null, 204);
        } catch (ReservationNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    private function transform(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'tenant_id' => $reservation->tenantId,
            'org_unit_id' => $reservation->orgUnitId,
            'row_version' => $reservation->rowVersion,
            'reservation_number' => $reservation->reservationNumber,
            'vehicle_id' => $reservation->vehicleId,
            'customer_id' => $reservation->customerId,
            'reserved_from' => $reservation->reservedFrom->format('Y-m-d H:i:s'),
            'reserved_to' => $reservation->reservedTo->format('Y-m-d H:i:s'),
            'status' => $reservation->status->value,
            'estimated_amount' => $reservation->estimatedAmount,
            'currency' => $reservation->currency,
            'notes' => $reservation->notes,
            'metadata' => $reservation->metadata,
            'is_active' => $reservation->isActive,
            'created_at' => $reservation->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $reservation->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
