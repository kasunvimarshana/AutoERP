<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleStatusChangeData;
use Modules\Vehicle\Enums\VehicleStatus;

final class ChangeVehicleStatusRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toData(): VehicleStatusChangeData
    {
        return new VehicleStatusChangeData(
            newStatus: VehicleStatus::from((string) $this->input('status')),
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            changedBy: $this->currentUserId(),
        );
    }
}
