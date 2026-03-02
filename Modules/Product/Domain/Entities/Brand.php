<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Brand
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private string $name,
        private ?string $description,
        private bool $isActive,
    ) {}

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function isActive(): bool { return $this->isActive; }
}
