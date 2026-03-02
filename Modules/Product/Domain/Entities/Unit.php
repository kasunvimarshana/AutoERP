<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Unit
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private string $name,
        private string $shortName,
        private bool $allowDecimal,
        private bool $isActive,
    ) {}

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getShortName(): string { return $this->shortName; }
    public function allowsDecimal(): bool { return $this->allowDecimal; }
    public function isActive(): bool { return $this->isActive; }
}
