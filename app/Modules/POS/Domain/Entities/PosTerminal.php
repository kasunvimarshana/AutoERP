<?php
declare(strict_types=1);
namespace Modules\POS\Domain\Entities;

/** A physical or virtual point-of-sale terminal assigned to a warehouse/location. */
class PosTerminal
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $warehouseId,
        private string $name,
        private string $code,
        private ?string $description,
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getDescription(): ?string { return $this->description; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
