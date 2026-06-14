<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleLinkData;

final class StoreRentalAgreementVehicleLinkRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'inbound_agreement_vehicle_id' => ['required', 'integer', 'min:1'],
            'outbound_agreement_vehicle_id' => ['required', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after:effective_from'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): RentalAgreementVehicleLinkData
    {
        return new RentalAgreementVehicleLinkData(
            inboundAgreementVehicleId: (int) $this->input('inbound_agreement_vehicle_id'),
            outboundAgreementVehicleId: (int) $this->input('outbound_agreement_vehicle_id'),
            effectiveFrom: (string) $this->input('effective_from'),
            effectiveTo: (string) $this->input('effective_to'),
            remarks: $this->filled('remarks') ? (string) $this->input('remarks') : null,
            createdBy: $this->currentUserId(),
        );
    }
}
