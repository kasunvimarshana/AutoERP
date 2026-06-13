<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceLineService
{
    public function __construct(
        private readonly VehicleServiceLineWriteService $writes,
        private readonly VehicleServiceLineCalculationService $calculations,
        private readonly VehicleServiceLineRuleService $rules,
    ) {}

    public function create(VehicleServiceJob $job, VehicleServiceLineData $data): VehicleServiceJobLine
    {
        return $this->writes->create($job, $data);
    }

    public function update(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceLineData $data,
    ): VehicleServiceJobLine {
        return $this->writes->update($job, $line, $data);
    }

    public function delete(VehicleServiceJob $job, VehicleServiceJobLine $line): void
    {
        $this->writes->delete($job, $line);
    }

    public function recalculateJob(VehicleServiceJob $job): VehicleServiceJob
    {
        return $this->calculations->recalculateJob($job);
    }

    public function isInventoryIssueLine(VehicleServiceJobLine $line): bool
    {
        return $this->rules->isInventoryIssueLine($line);
    }
}
