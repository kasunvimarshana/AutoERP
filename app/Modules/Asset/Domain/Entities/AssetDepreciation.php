<?php
declare(strict_types=1);
namespace Modules\Asset\Domain\Entities;

/**
 * A single periodic depreciation record for a FixedAsset.
 * type: scheduled | impairment | disposal_adjustment
 */
class AssetDepreciation
{
    public const TYPE_SCHEDULED            = 'scheduled';
    public const TYPE_IMPAIRMENT           = 'impairment';
    public const TYPE_DISPOSAL_ADJUSTMENT  = 'disposal_adjustment';

    public function __construct(
        private readonly ?int $id,
        private readonly int $tenantId,
        private readonly int $assetId,
        private readonly string $type,
        private readonly int $periodYear,
        private readonly int $periodMonth,
        private readonly float $amount,
        private readonly float $bookValueBefore,
        private readonly float $bookValueAfter,
        private readonly ?int $journalEntryId,
        private readonly \DateTimeInterface $depreciatedAt,
        private readonly ?\DateTimeInterface $createdAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getAssetId(): int { return $this->assetId; }
    public function getType(): string { return $this->type; }
    public function getPeriodYear(): int { return $this->periodYear; }
    public function getPeriodMonth(): int { return $this->periodMonth; }
    public function getAmount(): float { return $this->amount; }
    public function getBookValueBefore(): float { return $this->bookValueBefore; }
    public function getBookValueAfter(): float { return $this->bookValueAfter; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getDepreciatedAt(): \DateTimeInterface { return $this->depreciatedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
