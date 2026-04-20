<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class NumberingSequence
{
    public function __construct(
        private int $tenantId,
        private string $module,
        private string $documentType,
        private ?string $prefix = null,
        private ?string $suffix = null,
        private int $nextNumber = 1,
        private int $padding = 5,
        private bool $isActive = true,
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    public function getNextNumber(): int
    {
        return $this->nextNumber;
    }

    public function getPadding(): int
    {
        return $this->padding;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function generateNext(): string
    {
        $number = str_pad((string) $this->nextNumber, $this->padding, '0', STR_PAD_LEFT);
        $this->nextNumber++;
        $this->updatedAt = new \DateTimeImmutable;

        return ($this->prefix ?? '').$number.($this->suffix ?? '');
    }

    public function update(
        ?string $prefix,
        ?string $suffix,
        int $padding,
        bool $isActive,
    ): void {
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->padding = $padding;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
