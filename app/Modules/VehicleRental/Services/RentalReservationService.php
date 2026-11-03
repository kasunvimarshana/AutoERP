<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalBasis;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Models\RentalReservationHistory;

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
        private readonly VehicleUseAvailabilityBlocker $availability,
        private readonly AgreementService $agreements,
    ) {}

    public function create(array $data, int $tenantId, ?int $organizationUnitId, ?int $userId): RentalReservation
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId, $userId): RentalReservation {
            $this->validateReferences($data, $tenantId, $organizationUnitId);
            $start = CarbonImmutable::parse((string) $data['requested_start_at']);
            $end = CarbonImmutable::parse((string) $data['requested_end_at']);
            if (! $end->greaterThan($start)) {
                throw new InvalidArgumentException('Reservation end must be after its start.');
            }

            if (! empty($data['requested_vehicle_id'])) {
                $this->assertVehicleAvailable(
                    $tenantId,
                    (int) $data['requested_vehicle_id'],
                    $start->toIso8601String(),
                    $end->toIso8601String(),
                );
            }

            $customer = Customer::query()->forTenant($tenantId)->findOrFail((int) $data['customer_id']);
            $currency = CurrencyModel::query()->findOrFail((int) $data['currency_id']);

            $reservationNumber = $data['reservation_number'] ?? 'RR-'.strtoupper(bin2hex(random_bytes(4)));

            $reservation = RentalReservation::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId ?? $customer->organization_unit_id,
                'row_version' => 1,
                'reservation_number' => $reservationNumber,
                'customer_id' => $customer->id,
                'customer_name_snapshot' => $customer->name,
                'requested_vehicle_id' => $data['requested_vehicle_id'] ?? null,
                'requested_vehicle_category_id' => $data['requested_vehicle_category_id'] ?? null,
                'rental_mode' => $data['rental_mode'] instanceof DriverMode ? $data['rental_mode'] : DriverMode::from((string) $data['rental_mode']),
                'billing_cycle' => $data['billing_cycle'] instanceof RentalBasis ? $data['billing_cycle'] : RentalBasis::from((string) $data['billing_cycle']),
                'requested_start_at' => $start,
                'requested_end_at' => $end,
                'currency_id' => $currency->id,
                'currency_code_snapshot' => $currency->code,
                'estimated_amount' => $data['estimated_amount'] ?? '0.000000',
                'estimated_deposit_amount' => $data['estimated_deposit_amount'] ?? '0.000000',
                'status' => RentalReservationStatus::Draft,
                'source' => $data['source'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->recordHistory($reservation, null, RentalReservationStatus::Draft->value, $userId);

            return $reservation->load($this->relations());
        });
    }

    public function update(RentalReservation $reservation, array $data, int $expectedVersion, ?int $userId): RentalReservation
    {
        return DB::transaction(function () use ($reservation, $data, $expectedVersion, $userId): RentalReservation {
            $reservation = RentalReservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            $this->assertVersion($reservation, $expectedVersion);

            if (! in_array($reservation->status, [RentalReservationStatus::Draft, RentalReservationStatus::Pending], true)) {
                throw new InvalidArgumentException('Only draft or pending reservations can be edited.');
            }

            $merged = array_merge($reservation->toArray(), $data);
            $this->validateReferences($merged, (int) $reservation->tenant_id, $reservation->organization_unit_id);
            $start = CarbonImmutable::parse((string) $merged['requested_start_at']);
            $end = CarbonImmutable::parse((string) $merged['requested_end_at']);
            if (! $end->greaterThan($start)) {
                throw new InvalidArgumentException('Reservation end must be after its start.');
            }

            if (! empty($merged['requested_vehicle_id'])) {
                $this->assertVehicleAvailable(
                    (int) $reservation->tenant_id,
                    (int) $merged['requested_vehicle_id'],
                    $start->toIso8601String(),
                    $end->toIso8601String(),
                    excludeReservationId: (int) $reservation->getKey(),
                );
            }

            $reservation->fill([
                'customer_id' => $data['customer_id'] ?? $reservation->customer_id,
                'requested_vehicle_id' => array_key_exists('requested_vehicle_id', $data) ? $data['requested_vehicle_id'] : $reservation->requested_vehicle_id,
                'requested_vehicle_category_id' => array_key_exists('requested_vehicle_category_id', $data) ? $data['requested_vehicle_category_id'] : $reservation->requested_vehicle_category_id,
                'rental_mode' => isset($data['rental_mode']) ? ($data['rental_mode'] instanceof DriverMode ? $data['rental_mode'] : DriverMode::from((string) $data['rental_mode'])) : $reservation->rental_mode,
                'billing_cycle' => isset($data['billing_cycle']) ? ($data['billing_cycle'] instanceof RentalBasis ? $data['billing_cycle'] : RentalBasis::from((string) $data['billing_cycle'])) : $reservation->billing_cycle,
                'requested_start_at' => $start,
                'requested_end_at' => $end,
                'currency_id' => $data['currency_id'] ?? $reservation->currency_id,
                'estimated_amount' => $data['estimated_amount'] ?? $reservation->estimated_amount,
                'estimated_deposit_amount' => $data['estimated_deposit_amount'] ?? $reservation->estimated_deposit_amount,
                'source' => $data['source'] ?? $reservation->source,
                'remarks' => $data['remarks'] ?? $reservation->remarks,
                'row_version' => $expectedVersion + 1,
                'updated_by' => $userId,
            ]);
            $reservation->save();

            return $reservation->refresh()->load($this->relations());
        });
    }

    public function transition(
        RentalReservation $reservation,
        RentalReservationStatus $to,
        int $expectedVersion,
        ?int $userId = null,
        ?string $reason = null,
    ): RentalReservation {
        return DB::transaction(function () use ($reservation, $to, $expectedVersion, $userId, $reason): RentalReservation {
            $reservation = RentalReservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            $this->assertVersion($reservation, $expectedVersion);
            $from = $reservation->status;
            if ($from === $to) {
                return $reservation->load($this->relations());
            }

            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid reservation transition from {$from->value} to {$to->value}.");
            }

            if ($to === RentalReservationStatus::Confirmed && $reservation->requested_vehicle_id !== null) {
                $this->assertVehicleAvailable(
                    (int) $reservation->tenant_id,
                    (int) $reservation->requested_vehicle_id,
                    $reservation->requested_start_at->toIso8601String(),
                    $reservation->requested_end_at->toIso8601String(),
                    excludeReservationId: (int) $reservation->getKey(),
                );
            }

            $reservation->status = $to;
            if ($to === RentalReservationStatus::Confirmed) {
                $reservation->confirmed_by = $userId;
                $reservation->confirmed_at = now();
            } elseif ($to === RentalReservationStatus::Cancelled) {
                $reservation->cancelled_by = $userId;
                $reservation->cancelled_at = now();
            }
            $reservation->row_version = $expectedVersion + 1;
            $reservation->updated_by = $userId;
            $reservation->save();

            $this->recordHistory($reservation, $from->value, $to->value, $userId, $reason);

            return $reservation->refresh()->load($this->relations());
        });
    }

    public function convertToAgreement(
        RentalReservation $reservation,
        array $agreementPayload,
        int $expectedVersion,
        AgreementContext $context,
    ): CustomerAgreement {
        return DB::transaction(function () use ($reservation, $agreementPayload, $expectedVersion, $context): CustomerAgreement {
            $reservation = RentalReservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            $this->assertVersion($reservation, $expectedVersion);

            if ($reservation->status !== RentalReservationStatus::Confirmed) {
                throw new InvalidArgumentException('Only confirmed reservations can be converted to an agreement.');
            }

            $payload = array_merge([
                'reference' => 'CA-'.strtoupper(bin2hex(random_bytes(4))),
                'party_id' => $reservation->customer_id,
                'currency_id' => $reservation->currency_id,
                'agreed_on' => now()->toDateString(),
                'starts_on' => $reservation->requested_start_at->toDateString(),
                'ends_on' => $reservation->requested_end_at->toDateString(),
                'basis' => $reservation->billing_cycle->value,
                'driver_mode' => $reservation->rental_mode->value,
                'terms' => [
                    'base_rate' => (string) $reservation->estimated_amount,
                    'deposit_requirement' => (string) $reservation->estimated_deposit_amount,
                    'included_km' => '0',
                    'excess_km_rate' => '0',
                    'normal_ot_rate' => '0',
                    'night_out_rate' => '0',
                ],
                'notes' => 'Converted from reservation '.$reservation->reservation_number,
            ], $agreementPayload);

            $agreement = $this->agreements->create(AgreementKind::Customer, $context, $payload);

            $from = $reservation->status->value;
            $reservation->status = RentalReservationStatus::Converted;
            $reservation->converted_agreement_id = $agreement->getKey();
            $reservation->row_version = $expectedVersion + 1;
            $reservation->updated_by = $context->actorId;
            $reservation->save();

            $this->recordHistory($reservation, $from, RentalReservationStatus::Converted->value, $context->actorId, 'Converted to agreement '.$agreement->reference);

            return $agreement;
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalReservation::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(fn (Builder $scope) => $scope
                ->where('reservation_number', 'like', "%{$search}%")
                ->orWhere('customer_name_snapshot', 'like', "%{$search}%")
                ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$search}%"))
                ->orWhereHas('requestedVehicle', fn (Builder $v) => $v->where('vehicle_number', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%")));
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
        return ['customer', 'requestedVehicle.make', 'requestedVehicle.model', 'requestedVehicleCategory', 'currency', 'convertedAgreement'];
    }

    private function assertVehicleAvailable(int $tenantId, int $vehicleId, string $start, string $end, ?int $excludeReservationId = null): void
    {
        if ($this->availability->conflicts($tenantId, $vehicleId, $start, $end)) {
            throw new InvalidArgumentException(VehicleUseAvailabilityBlocker::REASON);
        }

        $conflictingReservation = RentalReservation::query()
            ->forTenant($tenantId)
            ->where('requested_vehicle_id', $vehicleId)
            ->where('status', RentalReservationStatus::Confirmed->value)
            ->when($excludeReservationId !== null, fn (Builder $q) => $q->whereKeyNot($excludeReservationId))
            ->where(fn (Builder $q) => $q->where('requested_start_at', '<', $end)->where('requested_end_at', '>', $start))
            ->exists();

        if ($conflictingReservation) {
            throw new InvalidArgumentException('The vehicle has a conflicting confirmed reservation.');
        }
    }

    private function validateReferences(array $data, int $tenantId, ?int $organizationUnitId): void
    {
        Customer::query()->forTenant($tenantId)->findOrFail((int) $data['customer_id']);
        CurrencyModel::query()->where('is_active', true)->findOrFail((int) $data['currency_id']);

        if (! empty($data['requested_vehicle_id'])) {
            Vehicle::query()->forTenant($tenantId)->findOrFail((int) $data['requested_vehicle_id']);
        }
        if (! empty($data['requested_vehicle_category_id'])) {
            VehicleCategory::query()->forTenant($tenantId)->findOrFail((int) $data['requested_vehicle_category_id']);
        }
    }

    private function assertVersion(RentalReservation $reservation, int $expectedVersion): void
    {
        if ((int) $reservation->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The rental reservation changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function recordHistory(RentalReservation $reservation, ?string $from, string $to, ?int $userId, ?string $reason = null): void
    {
        RentalReservationHistory::query()->create([
            'tenant_id' => $reservation->tenant_id,
            'organization_unit_id' => $reservation->organization_unit_id,
            'reservation_id' => $reservation->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'changed_by' => $userId,
            'changed_at' => now(),
        ]);
    }
}
