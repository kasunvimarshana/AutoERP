<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Entities;

class UomCategory
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private string $type,  // length|weight|volume|time|quantity|other
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}
