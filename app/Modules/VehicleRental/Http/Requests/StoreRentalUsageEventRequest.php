<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalUsageEventData;
use Modules\VehicleRental\Enums\RentalUsageEventType;

final class StoreRentalUsageEventRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['required', Rule::enum(RentalUsageEventType::class)],
            'quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function toData(): RentalUsageEventData
    {
        return new RentalUsageEventData(
            eventType: RentalUsageEventType::from((string) $this->input('event_type')),
            quantity: (string) $this->input('quantity'),
            remarks: $this->filled('remarks') ? (string) $this->input('remarks') : null,
            createdBy: $this->currentUserId(),
        );
    }
}
