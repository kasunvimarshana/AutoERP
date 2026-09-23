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

    public function create(
        VehicleServiceJob $job,
        VehicleServiceLineData $data,
        ?int $expectedVersion = null,
        ?int $actorId = null,
    ): VehicleServiceJobLine
    {
        return $this->writes->create($job, $data, $expectedVersion, $actorId);
    }

    public function update(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceLineData $data,
        ?int $expectedVersion = null,
        ?int $actorId = null,
    ): VehicleServiceJobLine {
        return $this->writes->update($job, $line, $data, $expectedVersion, $actorId);
    }

    public function delete(VehicleServiceJob $job, VehicleServiceJobLine $line, ?int $expectedVersion = null, ?int $actorId = null): void
    {
        $this->writes->delete($job, $line, $expectedVersion, $actorId);
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
