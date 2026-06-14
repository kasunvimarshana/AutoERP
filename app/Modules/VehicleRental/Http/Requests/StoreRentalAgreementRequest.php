<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalAgreementData;
use Modules\VehicleRental\DTOs\RentalRateSnapshotData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalType;

final class StoreRentalAgreementRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $decimal = ['required', 'decimal:0,6', 'min:0'];

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'agreement_number' => ['nullable', 'string', 'max:100'],
            'reservation_id' => ['nullable', 'integer', 'min:1'],
            'direction' => ['required', Rule::enum(RentalAgreementDirection::class)],
            'party_type' => ['required', Rule::enum(RentalPartyType::class)],
            'party_id' => ['required', 'integer', 'min:1'],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'billing_cycle' => ['required', Rule::enum(RentalBillingCycle::class)],
            'agreement_date' => ['required', 'date'],
            'start_at' => ['required', 'date'],
            'expected_end_at' => ['required', 'date', 'after:start_at'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'terms_snapshot' => ['nullable', 'array'],
            'remarks' => ['nullable', 'string'],
            'rate_snapshot' => ['required', 'array'],
            'rate_snapshot.base_rate' => $decimal,
            'rate_snapshot.rate_unit' => ['required', Rule::enum(RentalRateUnit::class)],
            'rate_snapshot.allowed_hours' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.allowed_km' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.extra_hour_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.extra_km_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.overtime_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.double_overtime_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.night_shift_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.weekend_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.holiday_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.driver_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.outstation_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.day_out_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.night_out_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.fuel_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.waiting_hour_rate' => ['nullable', 'decimal:0,6', 'min:0'],
            'rate_snapshot.tax_profile_id' => ['nullable', 'integer', 'min:1'],
            'rate_snapshot.currency_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toData(): RentalAgreementData
    {
        $rate = (array) $this->input('rate_snapshot');

        return new RentalAgreementData(
            tenantId: $this->tenantId(),
            direction: RentalAgreementDirection::from((string) $this->input('direction')),
            partyType: RentalPartyType::from((string) $this->input('party_type')),
            partyId: (int) $this->input('party_id'),
            rentalType: RentalType::from((string) $this->input('rental_type')),
            billingCycle: RentalBillingCycle::from((string) $this->input('billing_cycle')),
            agreementDate: (string) $this->input('agreement_date'),
            startAt: (string) $this->input('start_at'),
            expectedEndAt: (string) $this->input('expected_end_at'),
            rateSnapshot: new RentalRateSnapshotData(
                baseRate: (string) $rate['base_rate'],
                rateUnit: RentalRateUnit::from((string) $rate['rate_unit']),
                allowedHours: (string) ($rate['allowed_hours'] ?? '0.000000'),
                allowedKm: (string) ($rate['allowed_km'] ?? '0.000000'),
                extraHourRate: (string) ($rate['extra_hour_rate'] ?? '0.000000'),
                extraKmRate: (string) ($rate['extra_km_rate'] ?? '0.000000'),
                overtimeRate: (string) ($rate['overtime_rate'] ?? '0.000000'),
                doubleOvertimeRate: (string) ($rate['double_overtime_rate'] ?? '0.000000'),
                nightShiftRate: (string) ($rate['night_shift_rate'] ?? '0.000000'),
                weekendRate: (string) ($rate['weekend_rate'] ?? '0.000000'),
                holidayRate: (string) ($rate['holiday_rate'] ?? '0.000000'),
                driverRate: (string) ($rate['driver_rate'] ?? '0.000000'),
                outstationRate: (string) ($rate['outstation_rate'] ?? '0.000000'),
                dayOutRate: (string) ($rate['day_out_rate'] ?? '0.000000'),
                nightOutRate: (string) ($rate['night_out_rate'] ?? '0.000000'),
                fuelRate: (string) ($rate['fuel_rate'] ?? '0.000000'),
                waitingHourRate: (string) ($rate['waiting_hour_rate'] ?? '0.000000'),
                taxProfileId: isset($rate['tax_profile_id']) ? (int) $rate['tax_profile_id'] : null,
                currencyId: isset($rate['currency_id']) ? (int) $rate['currency_id'] : null,
            ),
            organizationUnitId: $this->organizationUnitId(),
            agreementNumber: $this->stringOrNull('agreement_number'),
            reservationId: $this->intOrNull('reservation_id'),
            currencyId: $this->intOrNull('currency_id'),
            termsSnapshot: $this->input('terms_snapshot'),
            remarks: $this->stringOrNull('remarks'),
            createdBy: $this->currentUserId(),
        );
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
