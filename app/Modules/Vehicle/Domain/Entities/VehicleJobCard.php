<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Entities;

class VehicleJobCard
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $vehicleId,
        private readonly string $jobCardNo,
        private readonly string $workflowStatus,
        private readonly ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    public function getJobCardNo(): string
    {
        return $this->jobCardNo;
    }

    public function getWorkflowStatus(): string
    {
        return $this->workflowStatus;
    }
}
