<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class AttendanceLog
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private ?int $biometricDeviceId,
        private \DateTimeInterface $punchTime,
        private string $punchType,
        private string $source,
        private array $rawData,
        private ?\DateTimeInterface $processedAt,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getBiometricDeviceId(): ?int
    {
        return $this->biometricDeviceId;
    }

    public function getPunchTime(): \DateTimeInterface
    {
        return $this->punchTime;
    }

    public function getPunchType(): string
    {
        return $this->punchType;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
