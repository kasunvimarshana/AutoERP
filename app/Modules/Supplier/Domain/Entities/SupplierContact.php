<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Entities;

class SupplierContact
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private string $name;

    private ?string $role;

    private ?string $email;

    private ?string $phone;

    private bool $isPrimary;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        string $name,
        ?string $role = null,
        ?string $email = null,
        ?string $phone = null,
        bool $isPrimary = false,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->name = $name;
        $this->role = $role;
        $this->email = $email;
        $this->phone = $phone;
        $this->isPrimary = $isPrimary;
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

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
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
        ?string $role,
        ?string $email,
        ?string $phone,
        bool $isPrimary,
    ): void {
        $this->name = $name;
        $this->role = $role;
        $this->email = $email;
        $this->phone = $phone;
        $this->isPrimary = $isPrimary;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
