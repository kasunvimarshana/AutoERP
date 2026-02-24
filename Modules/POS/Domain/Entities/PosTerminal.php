<?php

namespace Modules\POS\Domain\Entities;

class PosTerminal
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $name,
        private ?string $locationId,
        private bool $isActive,
        private string $openingBalance,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getLocationId(): ?string { return $this->locationId; }
    public function isActive(): bool { return $this->isActive; }
    public function getOpeningBalance(): string { return $this->openingBalance; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
