<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleOwnershipData;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleOwnerType;

abstract class VehicleOwnershipRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'owner_type' => ['nullable', Rule::enum(VehicleOwnerType::class)],
            'owner_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'ownership_type' => ['required', Rule::enum(VehicleOwnershipType::class)],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date'],
            'is_current' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): VehicleOwnershipData
    {
        return new VehicleOwnershipData(
            ownershipType: VehicleOwnershipType::from((string) $this->input('ownership_type')),
            startedAt: (string) $this->input('started_at'),
            ownerType: $this->filled('owner_type') ? (string) $this->input('owner_type') : null,
            ownerId: $this->filled('owner_id') ? (int) $this->input('owner_id') : null,
            customerId: $this->filled('customer_id') ? (int) $this->input('customer_id') : null,
            endedAt: $this->filled('ended_at') ? (string) $this->input('ended_at') : null,
            isCurrent: (bool) $this->input('is_current', true),
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }
}
