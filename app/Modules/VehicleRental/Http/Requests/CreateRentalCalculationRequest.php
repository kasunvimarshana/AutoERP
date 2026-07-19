<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalCalculationPeriodData;

final class CreateRentalCalculationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ];
    }

    public function toData(): RentalCalculationPeriodData
    {
        return new RentalCalculationPeriodData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            periodStart: (string) $this->validated('period_start'),
            periodEnd: (string) $this->validated('period_end'),
            actorId: $this->currentUserId(),
        );
    }
}
