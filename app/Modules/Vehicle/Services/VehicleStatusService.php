<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleStatusChangeData;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleStatusHistory;

final class VehicleStatusService
{
    public function recordInitial(Vehicle $vehicle, ?int $changedBy = null): void
    {
        VehicleStatusHistory::query()->create([
            'tenant_id' => $vehicle->tenant_id,
            'organization_unit_id' => $vehicle->organization_unit_id,
            'vehicle_id' => $vehicle->getKey(),
            'old_status' => null,
            'new_status' => $vehicle->status,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    public function change(Vehicle $vehicle, VehicleStatusChangeData $data): Vehicle
    {
        $from = $vehicle->status instanceof VehicleStatus ? $vehicle->status : VehicleStatus::from((string) $vehicle->status);
        $this->assertTransition($from, $data->newStatus);
        if ($from === $data->newStatus) { return $vehicle; }

        return DB::transaction(function () use ($vehicle, $data, $from): Vehicle {
            VehicleStatusHistory::query()->create([
                'tenant_id' => $vehicle->tenant_id,
                'organization_unit_id' => $vehicle->organization_unit_id,
                'vehicle_id' => $vehicle->getKey(),
                'old_status' => $from,
                'new_status' => $data->newStatus,
                'reason' => $data->reason,
                'changed_by' => $data->changedBy,
                'changed_at' => now(),
            ]);
            $vehicle->status = $data->newStatus;
            if ($data->newStatus === VehicleStatus::Active && $vehicle->approved_at === null) {
                $vehicle->approved_by = $data->changedBy;
                $vehicle->approved_at = now();
            }
            $vehicle->save();

            return $vehicle->refresh();
        });
    }

    public function changeTo(Vehicle $vehicle, VehicleStatus $status, ?int $changedBy = null, ?string $reason = null): Vehicle
    {
        return $this->change($vehicle, new VehicleStatusChangeData($status, $reason, $changedBy));
    }

    public function assertTransition(VehicleStatus $from, VehicleStatus $to): void
    {
        $terminal = [VehicleStatus::Sold, VehicleStatus::Scrapped];
        if ($from === $to) { return; }
        if (in_array($from, $terminal, true) && ! in_array($to, [VehicleStatus::Inactive, VehicleStatus::Blocked], true)) {
            throw new InvalidArgumentException('Invalid vehicle status transition.');
        }
        $allowed = [
            VehicleStatus::Active->value => [VehicleStatus::Inactive, VehicleStatus::UnderService, VehicleStatus::Rented, VehicleStatus::Reserved, VehicleStatus::Sold, VehicleStatus::Blocked, VehicleStatus::Scrapped],
            VehicleStatus::Inactive->value => [VehicleStatus::Active, VehicleStatus::Blocked, VehicleStatus::Scrapped],
            VehicleStatus::UnderService->value => [VehicleStatus::Active, VehicleStatus::Inactive, VehicleStatus::Blocked, VehicleStatus::Scrapped],
            VehicleStatus::Rented->value => [VehicleStatus::Active, VehicleStatus::UnderService, VehicleStatus::Blocked],
            VehicleStatus::Reserved->value => [VehicleStatus::Active, VehicleStatus::Rented, VehicleStatus::Inactive, VehicleStatus::Blocked],
            VehicleStatus::Blocked->value => [VehicleStatus::Active, VehicleStatus::Inactive, VehicleStatus::Scrapped],
            VehicleStatus::Sold->value => [VehicleStatus::Inactive, VehicleStatus::Blocked],
            VehicleStatus::Scrapped->value => [VehicleStatus::Inactive, VehicleStatus::Blocked],
        ];
        if (! in_array($to, $allowed[$from->value] ?? [], true)) {
            throw new InvalidArgumentException('Invalid vehicle status transition.');
        }
    }
}
