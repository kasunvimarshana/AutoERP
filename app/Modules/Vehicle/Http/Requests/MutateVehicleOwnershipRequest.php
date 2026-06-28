<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\Data\VersionedVehicleOwnershipCommand;

final class MutateVehicleOwnershipRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'organization_unit_id' => ['nullable', 'integer'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'ended_at' => ['nullable', 'date'],
        ];
    }

    public function toCommand(): VersionedVehicleOwnershipCommand
    {
        return new VersionedVehicleOwnershipCommand(
            expectedVersion: (int) $this->validated('expected_version'),
            endedAt: $this->validated('ended_at'),
        );
    }
}
