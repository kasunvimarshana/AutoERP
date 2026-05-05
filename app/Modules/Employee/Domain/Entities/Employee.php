<?php

declare(strict_types=1);

namespace Modules\Employee\Domain\Entities;

class Employee
{
    private ?int $id;

    private int $tenantId;

    private int $userId;

    private ?string $employeeCode;

    private ?int $orgUnitId;

    private ?string $jobTitle;

    private ?\DateTimeInterface $hireDate;

    private ?\DateTimeInterface $terminationDate;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private int $rowVersion;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        int $userId,
        ?string $employeeCode = null,
        ?int $orgUnitId = null,
        ?string $jobTitle = null,
        ?\DateTimeInterface $hireDate = null,
        ?\DateTimeInterface $terminationDate = null,
        ?array $metadata = null,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertDateRange($hireDate, $terminationDate);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->employeeCode = $employeeCode;
        $this->orgUnitId = $orgUnitId;
        $this->jobTitle = $jobTitle;
        $this->hireDate = $hireDate;
        $this->terminationDate = $terminationDate;
        $this->metadata = $metadata;
        $this->rowVersion = $rowVersion;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmployeeCode(): ?string
    {
        return $this->employeeCode;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function getHireDate(): ?\DateTimeInterface
    {
        return $this->hireDate;
    }

    public function getTerminationDate(): ?\DateTimeInterface
    {
        return $this->terminationDate;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        int $userId,
        ?string $employeeCode,
        ?int $orgUnitId,
        ?string $jobTitle,
        ?\DateTimeInterface $hireDate,
        ?\DateTimeInterface $terminationDate,
        ?array $metadata,
    ): void {
        $this->assertDateRange($hireDate, $terminationDate);

        $this->userId = $userId;
        $this->employeeCode = $employeeCode;
        $this->orgUnitId = $orgUnitId;
        $this->jobTitle = $jobTitle;
        $this->hireDate = $hireDate;
        $this->terminationDate = $terminationDate;
        $this->metadata = $metadata;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertDateRange(?\DateTimeInterface $hireDate, ?\DateTimeInterface $terminationDate): void
    {
        if ($hireDate === null || $terminationDate === null) {
            return;
        }

        if ($terminationDate < $hireDate) {
            throw new \InvalidArgumentException('Termination date cannot be before hire date.');
        }
    }
}
