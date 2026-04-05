<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Entities;

/**
 * Defines a barcode that belongs to a tenant, optionally linked to a domain entity.
 */
class BarcodeDefinition
{
    public function __construct(
        private readonly ?int              $id,
        private readonly int               $tenantId,
        private readonly string            $type,
        private readonly string            $value,
        private ?string                    $label,
        private readonly ?string           $entityType,
        private readonly ?int              $entityId,
        private array                      $metadata,
        private bool                       $isActive,
        private readonly ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface        $updatedAt,
    ) {}

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getId(): ?int                        { return $this->id; }
    public function getTenantId(): int                   { return $this->tenantId; }
    public function getType(): string                    { return $this->type; }
    public function getValue(): string                   { return $this->value; }
    public function getLabel(): ?string                  { return $this->label; }
    public function getEntityType(): ?string             { return $this->entityType; }
    public function getEntityId(): ?int                  { return $this->entityId; }
    public function getMetadata(): array                 { return $this->metadata; }
    public function isActive(): bool                     { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface  { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface  { return $this->updatedAt; }

    // ── Domain methods ────────────────────────────────────────────────────────

    public function activate(): void
    {
        $this->isActive   = true;
        $this->updatedAt  = new \DateTime();
    }

    public function deactivate(): void
    {
        $this->isActive   = false;
        $this->updatedAt  = new \DateTime();
    }

    public function updateLabel(string $label): void
    {
        $this->label      = $label;
        $this->updatedAt  = new \DateTime();
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata   = $metadata;
        $this->updatedAt  = new \DateTime();
    }
}
