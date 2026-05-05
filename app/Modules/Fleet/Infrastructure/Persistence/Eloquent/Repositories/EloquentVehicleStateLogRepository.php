<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Fleet\Domain\RepositoryInterfaces\VehicleStateLogRepositoryInterface;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Models\VehicleStateLogModel;

class EloquentVehicleStateLogRepository implements VehicleStateLogRepositoryInterface
{
    public function append(
        int $tenantId,
        int $vehicleId,
        string $fromState,
        string $toState,
        string $reason,
        ?string $referenceType,
        ?int $referenceId,
        ?int $triggeredBy,
    ): void {
        VehicleStateLogModel::create([
            'tenant_id'      => $tenantId,
            'vehicle_id'     => $vehicleId,
            'from_state'     => $fromState,
            'to_state'       => $toState,
            'reason'         => $reason,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'triggered_by'   => $triggeredBy,
            'created_at'     => now()->toDateTimeString(),
        ]);
    }
}
