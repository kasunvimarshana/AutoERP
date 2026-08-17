<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class RemoveVehicleServiceJobDiscountRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function reason(): string
    {
        return trim((string) $this->input('reason'));
    }

    public function changedBy(): ?int
    {
        return $this->currentUserId();
    }
}
