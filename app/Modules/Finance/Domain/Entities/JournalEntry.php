<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class JournalEntry
{
    /**
     * @param  array<JournalEntryLine>  $lines
     */
    public function __construct(
        private int $tenantId,
        private int $fiscalPeriodId,
        private \DateTimeInterface $entryDate,
        private int $createdBy,
        private string $entryType = 'manual',
        private ?string $entryNumber = null,
        private ?string $referenceType = null,
        private ?int $referenceId = null,
        private ?string $description = null,
        private ?\DateTimeInterface $postingDate = null,
        private string $status = 'draft',
        private bool $isReversed = false,
        private ?int $reversalEntryId = null,
        private ?int $postedBy = null,
        private ?\DateTimeInterface $postedAt = null,
        private array $lines = [],
        private int $rowVersion = 1,
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

    public function getFiscalPeriodId(): int
    {
        return $this->fiscalPeriodId;
    }

    public function getEntryNumber(): ?string
    {
        return $this->entryNumber;
    }

    public function getEntryType(): string
    {
        return $this->entryType;
    }

    public function getReferenceType(): ?string
    {
        return $this->referenceType;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getEntryDate(): \DateTimeInterface
    {
        return $this->entryDate;
    }

    public function getPostingDate(): ?\DateTimeInterface
    {
        return $this->postingDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isReversed(): bool
    {
        return $this->isReversed;
    }

    public function getReversalEntryId(): ?int
    {
        return $this->reversalEntryId;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getPostedBy(): ?int
    {
        return $this->postedBy;
    }

    public function getPostedAt(): ?\DateTimeInterface
    {
        return $this->postedAt;
    }

    /**
     * @return array<JournalEntryLine>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    /**
     * @param  array<JournalEntryLine>  $lines
     */
    public function update(
        int $fiscalPeriodId,
        string $entryType,
        ?string $referenceType,
        ?int $referenceId,
        ?string $description,
        \DateTimeInterface $entryDate,
        ?\DateTimeInterface $postingDate,
        array $lines,
    ): void {
        $this->fiscalPeriodId = $fiscalPeriodId;
        $this->entryType = $entryType;
        $this->referenceType = $referenceType;
        $this->referenceId = $referenceId;
        $this->description = $description;
        $this->entryDate = $entryDate;
        $this->postingDate = $postingDate;
        $this->lines = $lines;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markPosted(int $postedBy, ?\DateTimeInterface $postingDate = null): void
    {
        $this->status = 'posted';
        $this->postedBy = $postedBy;
        $this->postedAt = new \DateTimeImmutable;
        $this->postingDate = $postingDate ?? new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
