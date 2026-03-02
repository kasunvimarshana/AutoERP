<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class Warehouse
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private string $name,
        private string $code,
        private ?string $address,
        private bool $isActive,
        private bool $isDefault,
    ) {}

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getAddress(): ?string { return $this->address; }
    public function isActive(): bool { return $this->isActive; }
    public function isDefault(): bool { return $this->isDefault; }
}
