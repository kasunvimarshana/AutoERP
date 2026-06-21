<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\RentalReservation;

final class RentalReservationService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['pending', 'confirmed', 'cancelled'],
        'pending' => ['confirmed', 'cancelled', 'expired'],
        'confirmed' => ['converted', 'cancelled', 'expired'],
        'converted' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public function __construct(
        private readonly RentalNumberService $numbers,
        private readonly RentalAvailabilityService $availability,
        private readonly RentalReferenceValidator $references,
        private readonly RentalStatusHistoryService $history,
    ) {}

    public function create(array $data, int $tenantId, ?int $organizationUnitId, ?int $userId): RentalReservation
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId, $userId): RentalReservation {
            $this->validate($data, $tenantId, $organizationUnitId);
            if (! empty($data['requested_vehicle_id'])) {
                $this->availability->assertVehicle(
                    $tenantId,
                    $organizationUnitId,
                    (int) $data['requested_vehicle_id'],
                    (string) $data['requested_start_at'],
                    (string) $data['requested_end_at'],
                );
            }

            $reservation = RentalReservation::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'reservation_number' => $data['reservation_number'] ?? $this->numbers->next($tenantId, $organizationUnitId, 'vehicle_rental_reservation', 'RR-'),
                'customer_id' => $data['customer_id'],
                'requested_vehicle_id' => $data['requested_vehicle_id'] ?? null,
                'requested_vehicle_category_id' => $data['requested_vehicle_category_id'] ?? null,
                'rental_mode' => $data['rental_mode'],
                'billing_cycle' => $data['billing_cycle'],
                'requested_start_at' => $data['requested_start_at'],
                'requested_end_at' => $data['requested_end_at'],
                'currency_id' => $data['currency_id'],
                'estimated_amount' => $data['estimated_amount'] ?? '0.000000',
                'estimated_deposit_amount' => $data['estimated_deposit_amount'] ?? '0.000000',
                'status' => RentalReservationStatus::Draft->value,
                'source' => $data['source'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->history->record($reservation, null, RentalReservationStatus::Draft->value, $userId);

            return $reservation->load($this->relations());
        });
    }

    public function update(RentalReservation $reservation, array $data, ?int $userId): RentalReservation
    {
        return DB::transaction(function () use ($reservation, $data, $userId): RentalReservation {
            $reservation = RentalReservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            if (! in_array($reservation->status, [RentalReservationStatus::Draft, RentalReservationStatus::Pending], true)) {
                throw new InvalidArgumentException('Only draft or pending reservations can be edited.');
            }

            $merged = array_merge($reservation->toArray(), $data);
            $this->validate($merged, (int) $reservation->tenant_id, $reservation->organization_unit_id);
            if (! empty($merged['requested_vehicle_id'])) {
                $this->availability->assertVehicle(
                    (int) $reservation->tenant_id,
                    $reservation->organization_unit_id,
                    (int) $merged['requested_vehicle_id'],
                    (string) $merged['requested_start_at'],
                    (string) $merged['requested_end_at'],
                    excludeReservationId: (int) $reservation->getKey(),
                );
            }

            $reservation->fill(array_intersect_key($data, array_flip([
                'customer_id', 'requested_vehicle_id', 'requested_vehicle_category_id', 'rental_mode', 'billing_cycle',
                'requested_start_at', 'requested_end_at', 'currency_id', 'estimated_amount',
                'estimated_deposit_amount', 'source', 'remarks',
            ])));
            $reservation->row_version = ((int) $reservation->row_version) + 1;
            $reservation->updated_by = $userId;
            $reservation->save();

            return $reservation->refresh()->load($this->relations());
        });
    }

    public function transition(RentalReservation $reservation, RentalReservationStatus $to, ?int $userId = null, ?string $reason = null): RentalReservation
    {
        return DB::transaction(function () use ($reservation, $to, $userId, $reason): RentalReservation {
            $reservation = RentalReservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            $from = $reservation->status;
            if ($from === $to) {
                return $reservation->load($this->relations());
            }
            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid reservation transition from {$from->value} to {$to->value}.");
            }
            if ($to === RentalReservationStatus::Confirmed && $reservation->requested_vehicle_id !== null) {
                $this->availability->assertVehicle(
                    (int) $reservation->tenant_id,
                    $reservation->organization_unit_id,
                    (int) $reservation->requested_vehicle_id,
                    $reservation->requested_start_at->toDateTimeString(),
                    $reservation->requested_end_at->toDateTimeString(),
                    excludeReservationId: (int) $reservation->getKey(),
                );
            }

            $reservation->status = $to;
            $reservation->confirmed_by = $to === RentalReservationStatus::Confirmed ? $userId : $reservation->confirmed_by;
            $reservation->confirmed_at = $to === RentalReservationStatus::Confirmed ? now() : $reservation->confirmed_at;
            $reservation->cancelled_by = $to === RentalReservationStatus::Cancelled ? $userId : $reservation->cancelled_by;
            $reservation->cancelled_at = $to === RentalReservationStatus::Cancelled ? now() : $reservation->cancelled_at;
            $reservation->updated_by = $userId;
            $reservation->save();
            $this->history->record($reservation, $from->value, $to->value, $userId, $reason);

            return $reservation->refresh()->load($this->relations());
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalReservation::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(fn (Builder $scope) => $scope
                ->where('reservation_number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%"))
                ->orWhereHas('requestedVehicle', fn (Builder $vehicle) => $vehicle
                    ->where('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")));
        }
        foreach (['status', 'customer_id', 'requested_vehicle_id', 'requested_vehicle_category_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('requested_start_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('requested_start_at', '<=', $filters['date_to']);
        }

        return $query->latest('requested_start_at')->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return ['customer', 'requestedVehicle.make', 'requestedVehicle.model', 'requestedVehicle.category', 'requestedVehicleCategory', 'currency', 'agreement'];
    }

    private function validate(array $data, int $tenantId, ?int $organizationUnitId): void
    {
        $start = CarbonImmutable::parse((string) $data['requested_start_at']);
        $end = CarbonImmutable::parse((string) $data['requested_end_at']);
        if (! $end->greaterThan($start)) {
            throw new InvalidArgumentException('Reservation end must be after its start.');
        }

        $this->references->customer((int) $data['customer_id'], $tenantId, $organizationUnitId);
        $this->references->currency((int) $data['currency_id']);
        $this->references->vehicleCategory(
            isset($data['requested_vehicle_category_id']) ? (int) $data['requested_vehicle_category_id'] : null,
            $tenantId,
            $organizationUnitId,
        );
    }
}
