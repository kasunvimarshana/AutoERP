<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;

final class SaveVehicleServiceCommissionPolicyRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
            'commission_type' => ['required', Rule::enum(VehicleServiceCommissionType::class)],
            'commission_value' => ['required', 'decimal:0,6', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function commissionType(): VehicleServiceCommissionType
    {
        return VehicleServiceCommissionType::from((string) $this->input('commission_type'));
    }

    public function commissionValue(): string
    {
        return (string) $this->input('commission_value');
    }

    public function isActive(): bool
    {
        return $this->boolean('is_active');
    }

    public function expectedVersion(): ?int
    {
        return $this->filled('expected_version') ? (int) $this->input('expected_version') : null;
    }
}
