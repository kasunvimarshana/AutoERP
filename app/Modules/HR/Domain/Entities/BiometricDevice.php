<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\ValueObjects\BiometricDeviceStatus;

class BiometricDevice
{
    public function __construct(
        private readonly int $tenantId,
        private string $name,
        private string $code,
        private string $deviceType,
        private string $ipAddress,
        private int $port,
        private string $location,
        private ?int $orgUnitId,
        private BiometricDeviceStatus $status,
        private array $metadata,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getStatus(): BiometricDeviceStatus
    {
        return $this->status;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        string $name,
        string $code,
        string $deviceType,
        string $ipAddress,
        int $port,
        string $location,
        ?int $orgUnitId,
        BiometricDeviceStatus $status,
        array $metadata,
    ): void {
        $this->name = $name;
        $this->code = $code;
        $this->deviceType = $deviceType;
        $this->ipAddress = $ipAddress;
        $this->port = $port;
        $this->location = $location;
        $this->orgUnitId = $orgUnitId;
        $this->status = $status;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
