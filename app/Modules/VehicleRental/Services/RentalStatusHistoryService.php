<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\VehicleRental\Models\RentalStatusHistory;

final class RentalStatusHistoryService
{
    public function record(Model $model, ?string $oldStatus, string $newStatus, ?int $changedBy = null, ?string $reason = null): void
    {
        RentalStatusHistory::query()->create([
            'tenant_id' => $model->getAttribute('tenant_id'),
            'organization_unit_id' => $model->getAttribute('organization_unit_id'),
            'entity_type' => class_basename($model),
            'entity_id' => $model->getKey(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
