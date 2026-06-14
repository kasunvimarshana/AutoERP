<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Enums\RentalType;

final class ListRentalRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(array_unique([
                ...array_column(RentalAgreementStatus::cases(), 'value'),
                ...array_column(RentalReservationStatus::cases(), 'value'),
            ]))],
            'direction' => ['nullable', Rule::enum(RentalAgreementDirection::class)],
            'party_type' => ['nullable', Rule::enum(RentalPartyType::class)],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'rental_type' => ['nullable', Rule::enum(RentalType::class)],
            'billing_cycle' => ['nullable', Rule::enum(RentalBillingCycle::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'overdue' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
