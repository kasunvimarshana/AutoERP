<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleData;
use Modules\VehicleRental\Enums\RentalPartyType;

final class StoreRentalAgreementVehicleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'owner_party_type' => ['nullable', Rule::enum(RentalPartyType::class), 'required_with:owner_party_id'],
            'owner_party_id' => ['nullable', 'integer', 'min:1', 'required_with:owner_party_type'],
            'allocated_from' => ['required', 'date'],
            'allocated_to' => ['nullable', 'date', 'after:allocated_from'],
            'start_odometer' => ['required', 'decimal:0,6', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function toData(): RentalAgreementVehicleData
    {
        return new RentalAgreementVehicleData(
            vehicleId: (int) $this->input('vehicle_id'),
            allocatedFrom: (string) $this->input('allocated_from'),
            startOdometer: (string) $this->input('start_odometer'),
            allocatedTo: $this->filled('allocated_to') ? (string) $this->input('allocated_to') : null,
            ownerPartyType: $this->filled('owner_party_type')
                ? RentalPartyType::from((string) $this->input('owner_party_type'))
                : null,
            ownerPartyId: $this->filled('owner_party_id') ? (int) $this->input('owner_party_id') : null,
            remarks: $this->filled('remarks') ? (string) $this->input('remarks') : null,
            createdBy: $this->currentUserId(),
        );
    }
}
