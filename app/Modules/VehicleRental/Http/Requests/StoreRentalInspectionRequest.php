<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalInspectionData;

final class StoreRentalInspectionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'inspected_at' => ['required', 'date'],
            'odometer' => ['required', 'decimal:0,6', 'min:0'],
            'fuel_level' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'condition_notes' => ['nullable', 'string'],
            'damage_notes' => ['nullable', 'string'],
            'damage_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'is_damage_billable' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string', 'max:500'],
        ];
    }

    public function toData(): RentalInspectionData
    {
        return new RentalInspectionData(
            inspectedAt: (string) $this->input('inspected_at'),
            odometer: (string) $this->input('odometer'),
            fuelLevel: $this->filled('fuel_level') ? (string) $this->input('fuel_level') : null,
            conditionNotes: $this->filled('condition_notes') ? (string) $this->input('condition_notes') : null,
            damageNotes: $this->filled('damage_notes') ? (string) $this->input('damage_notes') : null,
            attachments: $this->input('attachments'),
            inspectedBy: $this->currentUserId(),
            damageAmount: (string) $this->input('damage_amount', '0.000000'),
            isDamageBillable: $this->boolean('is_damage_billable'),
        );
    }
}
