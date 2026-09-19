<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceJobDiscountData;
use Modules\VehicleService\Enums\VehicleServiceDiscountCalculationType;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceJobDiscountRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'calculation_type' => ['required', Rule::enum(VehicleServiceDiscountCalculationType::class)],
            'rate' => ['nullable', 'required_if:calculation_type,percentage', 'decimal:0,6', 'between:0,100'],
            'fixed_amount' => ['nullable', 'required_if:calculation_type,fixed', 'decimal:0,6', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function toData(): VehicleServiceJobDiscountData
    {
        return new VehicleServiceJobDiscountData(
            calculationType: VehicleServiceDiscountCalculationType::from((string) $this->input('calculation_type')),
            rate: (string) $this->input('rate', '0.000000'),
            fixedAmount: (string) $this->input('fixed_amount', '0.000000'),
            reason: trim((string) $this->input('reason')),
            changedBy: $this->currentUserId(),
        );
    }
}
