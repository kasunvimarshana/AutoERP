<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\RepositoryInterfaces;

use Modules\Fleet\Domain\Entities\DepreciationSchedule;

interface DepreciationScheduleRepositoryInterface
{
    public function findByVehicle(int $vehicleId): ?DepreciationSchedule;

    public function save(DepreciationSchedule $schedule): DepreciationSchedule;

    public function incrementAccumulated(int $vehicleId, string $amount): void;
}
