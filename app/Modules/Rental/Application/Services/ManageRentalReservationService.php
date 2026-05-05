<?php declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Rental\Application\Contracts\ManageRentalReservationServiceInterface;
use Modules\Rental\Domain\Entities\RentalReservation;
use Modules\Rental\Domain\RepositoryInterfaces\RentalReservationRepositoryInterface;

class ManageRentalReservationService implements ManageRentalReservationServiceInterface
{
    public function __construct(
        private readonly RentalReservationRepositoryInterface $reservations,
    ) {}

    public function create(array $data): RentalReservation
    {
        return DB::transaction(function () use ($data): RentalReservation {
            $reservation = new RentalReservation(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                vehicleId: $data['vehicle_id'],
                customerId: $data['customer_id'],
                driverId: $data['driver_id'] ?? null,
                reservationNumber: $this->generateReservationNumber(),
                startAt: new \DateTime($data['start_at']),
                expectedReturnAt: new \DateTime($data['expected_return_at']),
                billingUnit: $data['billing_unit'] ?? 'day',
                baseRate: (string) $data['base_rate'],
                estimatedDistance: (int) ($data['estimated_distance'] ?? 0),
                estimatedAmount: (string) $data['estimated_amount'],
                status: 'draft',
                notes: $data['notes'] ?? null,
            );

            $this->reservations->create($reservation);
            return $reservation;
        });
    }

    public function update(int $tenantId, string $id, array $data): RentalReservation
    {
        return DB::transaction(function () use ($tenantId, $id, $data): RentalReservation {
            $reservation = $this->reservations->findById($id);
            if (!$reservation || $reservation->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Reservation not found');
            }

            $this->reservations->update($reservation);
            return $this->reservations->findById($id);
        });
    }

    public function find(int $tenantId, string $id): RentalReservation
    {
        $reservation = $this->reservations->findById($id);
        if (!$reservation || $reservation->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Reservation not found');
        }
        return $reservation;
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array
    {
        $result = $this->reservations->getByStatus((string) $tenantId, 'all', $page, $perPage);
        return [
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function confirm(int $tenantId, string $id): RentalReservation
    {
        return DB::transaction(function () use ($tenantId, $id): RentalReservation {
            $reservation = $this->find($tenantId, $id);
            $reservation->confirm();
            $this->reservations->update($reservation);
            return $this->reservations->findById($id);
        });
    }

    public function cancel(int $tenantId, string $id): RentalReservation
    {
        return DB::transaction(function () use ($tenantId, $id): RentalReservation {
            $reservation = $this->find($tenantId, $id);
            $reservation->cancel();
            $this->reservations->update($reservation);
            return $this->reservations->findById($id);
        });
    }

    private function generateReservationNumber(): string
    {
        return 'RES-' . date('Ymd') . '-' . Str::random(6);
    }
}
