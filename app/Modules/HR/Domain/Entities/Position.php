<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;

class Position
{
    private ?int $id;

    private int $tenantId;

    private Name $name;

    private ?Code $code;

    private ?string $description;

    private ?string $grade;

    private ?int $departmentId;

    private Metadata $metadata;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        Name $name,
        ?Code $code = null,
        ?string $description = null,
        ?string $grade = null,
        ?int $departmentId = null,
        ?Metadata $metadata = null,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id           = $id;
        $this->tenantId     = $tenantId;
        $this->name         = $name;
        $this->code         = $code;
        $this->description  = $description;
        $this->grade        = $grade;
        $this->departmentId = $departmentId;
        $this->metadata     = $metadata ?? new Metadata([]);
        $this->isActive     = $isActive;
        $this->createdAt    = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt    = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getCode(): ?Code
    {
        return $this->code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function updateDetails(
        Name $name,
        ?Code $code,
        ?string $description,
        ?string $grade,
        ?int $departmentId,
        ?Metadata $metadata,
        bool $isActive
    ): void {
        $this->name         = $name;
        $this->code         = $code;
        $this->description  = $description;
        $this->grade        = $grade;
        $this->departmentId = $departmentId;
        $this->metadata     = $metadata ?? new Metadata([]);
        $this->isActive     = $isActive;
        $this->updatedAt    = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
