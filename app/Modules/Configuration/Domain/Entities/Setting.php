<?php
declare(strict_types=1);
namespace Modules\Configuration\Domain\Entities;

class Setting
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $key,
        private mixed $value,
        private string $type,
        private ?string $description,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getKey(): string { return $this->key; }
    public function getValue(): mixed { return $this->value; }
    public function getType(): string { return $this->type; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function updateValue(mixed $value): void { $this->value = $value; }
}
