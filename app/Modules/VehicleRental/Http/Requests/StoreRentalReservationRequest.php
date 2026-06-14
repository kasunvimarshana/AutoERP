<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalReservationData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalType;

final class StoreRentalReservationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'reservation_number' => ['nullable', 'string', 'max:100'],
            'direction' => ['required', Rule::enum(RentalAgreementDirection::class)],
            'party_type' => ['required', Rule::enum(RentalPartyType::class)],
            'party_id' => ['required', 'integer', 'min:1'],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'start_at' => ['required', 'date'],
            'expected_end_at' => ['required', 'date', 'after:start_at'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function toData(): RentalReservationData
    {
        return new RentalReservationData(
            tenantId: $this->tenantId(),
            direction: RentalAgreementDirection::from((string) $this->input('direction')),
            partyType: RentalPartyType::from((string) $this->input('party_type')),
            partyId: (int) $this->input('party_id'),
            rentalType: RentalType::from((string) $this->input('rental_type')),
            startAt: (string) $this->input('start_at'),
            expectedEndAt: (string) $this->input('expected_end_at'),
            organizationUnitId: $this->organizationUnitId(),
            reservationNumber: $this->stringOrNull('reservation_number'),
            vehicleId: $this->intOrNull('vehicle_id'),
            currencyId: $this->intOrNull('currency_id'),
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
