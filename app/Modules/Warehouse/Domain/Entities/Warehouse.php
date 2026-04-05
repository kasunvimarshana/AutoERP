<?php
declare(strict_types=1);
namespace Modules\Warehouse\Domain\Entities;
class Warehouse {
    public function __construct(
        private ?int $id, private int $tenantId, private string $name, private string $code,
        private string $type, // standard|cold_storage|hazmat|bonded
        private string $address, private bool $isActive,
        private ?int $managerId, private ?array $metadata,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getType(): string { return $this->type; }
    public function getAddress(): string { return $this->address; }
    public function isActive(): bool { return $this->isActive; }
    public function getManagerId(): ?int { return $this->managerId; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
