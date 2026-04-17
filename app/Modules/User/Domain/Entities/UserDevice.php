<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

class UserDevice
{
    private ?int $id;

    private int $userId;

    private string $deviceToken;

    private ?string $platform;

    private ?string $deviceName;

    private ?\DateTimeInterface $lastActiveAt;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $userId,
        string $deviceToken,
        ?string $platform = null,
        ?string $deviceName = null,
        ?\DateTimeInterface $lastActiveAt = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->deviceToken = $deviceToken;
        $this->platform = $platform;
        $this->deviceName = $deviceName;
        $this->lastActiveAt = $lastActiveAt;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDeviceToken(): string
    {
        return $this->deviceToken;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function getLastActiveAt(): ?\DateTimeInterface
    {
        return $this->lastActiveAt;
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
