<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Fleet\Domain\Entities\DepreciationSchedule;
use Modules\Fleet\Domain\RepositoryInterfaces\DepreciationScheduleRepositoryInterface;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Models\DepreciationScheduleModel;

class EloquentDepreciationScheduleRepository implements DepreciationScheduleRepositoryInterface
{
    public function findByVehicle(int $vehicleId): ?DepreciationSchedule
    {
        $model = DepreciationScheduleModel::where('vehicle_id', $vehicleId)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(DepreciationSchedule $schedule): DepreciationSchedule
    {
        $data = [
            'tenant_id'                    => $schedule->tenantId,
            'vehicle_id'                   => $schedule->vehicleId,
            'method'                       => $schedule->method,
            'useful_life_months'           => $schedule->usefulLifeMonths,
            'salvage_value'                => $schedule->salvageValue,
            'depreciable_amount'           => $schedule->depreciableAmount,
            'monthly_depreciation_amount'  => $schedule->monthlyDepreciationAmount,
            'accumulated_depreciation'     => $schedule->accumulatedDepreciation,
            'start_date'                   => $schedule->startDate,
            'end_date'                     => $schedule->endDate,
            'is_active'                    => $schedule->isActive,
        ];

        if ($schedule->id !== null) {
            $model = DepreciationScheduleModel::findOrFail($schedule->id);
            $model->update($data);
        } else {
            $model = DepreciationScheduleModel::create($data);
        }

        return $this->toEntity($model->fresh());
    }

    public function incrementAccumulated(int $vehicleId, string $amount): void
    {
        DepreciationScheduleModel::where('vehicle_id', $vehicleId)
            ->increment('accumulated_depreciation', (float) $amount);
    }

    private function toEntity(DepreciationScheduleModel $m): DepreciationSchedule
    {
        return new DepreciationSchedule(
            tenantId:                    $m->tenant_id,
            vehicleId:                   $m->vehicle_id,
            method:                      $m->method,
            usefulLifeMonths:            $m->useful_life_months,
            salvageValue:                (string) $m->salvage_value,
            depreciableAmount:           (string) $m->depreciable_amount,
            monthlyDepreciationAmount:   (string) $m->monthly_depreciation_amount,
            accumulatedDepreciation:     (string) $m->accumulated_depreciation,
            startDate:                   $m->start_date,
            endDate:                     $m->end_date,
            isActive:                    (bool) $m->is_active,
            id:                          $m->id,
        );
    }
}
