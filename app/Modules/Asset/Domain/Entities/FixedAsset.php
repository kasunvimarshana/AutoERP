<?php
declare(strict_types=1);
namespace Modules\Asset\Domain\Entities;

/**
 * A fixed asset (equipment, vehicle, machinery, software, building component, etc.).
 * status: active | disposed | under_maintenance | sold
 * depreciationMethod: straight_line | declining_balance | none
 */
class FixedAsset
{
    public const STATUS_ACTIVE            = 'active';
    public const STATUS_DISPOSED          = 'disposed';
    public const STATUS_UNDER_MAINTENANCE = 'under_maintenance';
    public const STATUS_SOLD              = 'sold';

    public const DEPRECIATION_STRAIGHT_LINE    = 'straight_line';
    public const DEPRECIATION_DECLINING_BALANCE = 'declining_balance';
    public const DEPRECIATION_NONE             = 'none';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $code,
        private string $name,
        private ?string $description,
        private string $category,           // e.g. Equipment, Vehicle, Furniture
        private ?int $locationId,           // warehouse/location
        private ?int $assignedTo,           // employee / user
        private float $purchaseCost,
        private float $residualValue,       // salvage value at end of life
        private int $usefulLifeMonths,
        private string $depreciationMethod,
        private ?int $assetAccountId,       // links to COA
        private ?int $depreciationAccountId,
        private string $status,
        private \DateTimeInterface $purchaseDate,
        private ?\DateTimeInterface $disposalDate,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getCategory(): string { return $this->category; }
    public function getLocationId(): ?int { return $this->locationId; }
    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function getPurchaseCost(): float { return $this->purchaseCost; }
    public function getResidualValue(): float { return $this->residualValue; }
    public function getUsefulLifeMonths(): int { return $this->usefulLifeMonths; }
    public function getDepreciationMethod(): string { return $this->depreciationMethod; }
    public function getAssetAccountId(): ?int { return $this->assetAccountId; }
    public function getDepreciationAccountId(): ?int { return $this->depreciationAccountId; }
    public function getStatus(): string { return $this->status; }
    public function getPurchaseDate(): \DateTimeInterface { return $this->purchaseDate; }
    public function getDisposalDate(): ?\DateTimeInterface { return $this->disposalDate; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function dispose(\DateTimeInterface $date): void
    {
        if ($this->status === self::STATUS_DISPOSED || $this->status === self::STATUS_SOLD) {
            throw new \DomainException("Asset is already disposed/sold.");
        }
        $this->status      = self::STATUS_DISPOSED;
        $this->disposalDate = $date;
    }

    public function sell(\DateTimeInterface $date): void
    {
        if ($this->status === self::STATUS_DISPOSED || $this->status === self::STATUS_SOLD) {
            throw new \DomainException("Asset is already disposed/sold.");
        }
        $this->status      = self::STATUS_SOLD;
        $this->disposalDate = $date;
    }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }

    /**
     * Calculate straight-line monthly depreciation charge.
     * Returns 0 if non-depreciable.
     */
    public function monthlyDepreciation(): float
    {
        if ($this->depreciationMethod === self::DEPRECIATION_NONE || $this->usefulLifeMonths <= 0) {
            return 0.0;
        }
        return ($this->purchaseCost - $this->residualValue) / $this->usefulLifeMonths;
    }
}
