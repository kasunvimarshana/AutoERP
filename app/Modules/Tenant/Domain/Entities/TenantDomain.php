<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

class TenantDomain
{
    private ?int $id;

    private int $tenantId;

    private string $domain;

    private bool $isPrimary;

    private bool $isVerified;

    private ?\DateTimeInterface $verifiedAt;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $domain,
        bool $isPrimary = false,
        bool $isVerified = false,
        ?\DateTimeInterface $verifiedAt = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->domain = $domain;
        $this->isPrimary = $isPrimary;
        $this->isVerified = $isVerified;
        $this->verifiedAt = $verifiedAt;
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

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getVerifiedAt(): ?\DateTimeInterface
    {
        return $this->verifiedAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(string $domain, bool $isPrimary, bool $isVerified, ?\DateTimeInterface $verifiedAt): void
    {
        $this->domain = $domain;
        $this->isPrimary = $isPrimary;
        $this->isVerified = $isVerified;
        $this->verifiedAt = $verifiedAt;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
