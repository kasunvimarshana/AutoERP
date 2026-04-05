<?php
declare(strict_types=1);
namespace Modules\Supplier\Domain\Entities;

class Supplier
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private string $code,
        private ?string $email,
        private ?string $phone,
        private ?string $address,
        private bool $isActive,
        private ?array $metadata,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getAddress(): ?string { return $this->address; }
    public function isActive(): bool { return $this->isActive; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
