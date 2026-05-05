<?php declare(strict_types=1);

namespace Modules\Asset\Domain\Entities;

/**
 * Asset Entity - Base class for all asset types (vehicles, equipment, tools)
 *
 * This is the root entity for asset management, representing any tangible item
 * owned by the business that has depreciation, maintenance, and lifecycle tracking.
 *
 * @package Modules\Asset\Domain\Entities
 */
final class Asset
{
    /**
     * Unique identifier (UUID)
     *
     * @var string
     */
    private string $id;

    /**
     * Tenant ID for multi-tenancy isolation
     *
     * @var string
     */
    private string $tenantId;

    /**
     * Asset name/description
     *
     * @var string
     */
    private string $name;

    /**
     * Asset type (vehicle, equipment, tool, etc.)
     *
     * @var string
     */
    private string $type;

    /**
     * Unique asset identifier (Serial number, VIN, etc.)
     *
     * @var string|null
     */
    private ?string $serialNumber;

    /**
     * Owner of this asset
     *
     * @var string (UUID reference to AssetOwner)
     */
    private string $assetOwnerId;

    /**
     * Purchase date
     *
     * @var \DateTime
     */
    private \DateTime $purchaseDate;

    /**
     * Acquisition cost (original purchase price)
     *
     * @var string (DECIMAL as string for precision)
     */
    private string $acquisitionCost;

    /**
     * Current status of the asset
     *
     * Values: active, maintenance, retired, sold, damaged
     *
     * @var string
     */
    private string $status;

    /**
     * Depreciation method
     *
     * Values: straight_line, declining_balance, units_of_production
     *
     * @var string
     */
    private string $depreciationMethod;

    /**
     * Useful life in years
     *
     * @var int
     */
    private int $usefulLifeYears;

    /**
     * Salvage value at end of useful life
     *
     * @var string (DECIMAL as string)
     */
    private string $salvageValue;

    /**
     * Date the asset was created in the system
     *
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Date the asset was last updated
     *
     * @var \DateTime|null
     */
    private ?\DateTime $updatedAt;

    /**
     * Date the asset was soft-deleted
     *
     * @var \DateTime|null
     */
    private ?\DateTime $deletedAt;

    /**
     * Constructor
     *
     * @param string $id
     * @param string $tenantId
     * @param string $name
     * @param string $type
     * @param string|null $serialNumber
     * @param string $assetOwnerId
     * @param \DateTime $purchaseDate
     * @param string $acquisitionCost
     * @param string $status
     * @param string $depreciationMethod
     * @param int $usefulLifeYears
     * @param string $salvageValue
     */
    public function __construct(
        string $id,
        string $tenantId,
        string $name,
        string $type,
        ?string $serialNumber,
        string $assetOwnerId,
        \DateTime $purchaseDate,
        string $acquisitionCost,
        string $status,
        string $depreciationMethod,
        int $usefulLifeYears,
        string $salvageValue,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->type = $type;
        $this->serialNumber = $serialNumber;
        $this->assetOwnerId = $assetOwnerId;
        $this->purchaseDate = $purchaseDate;
        $this->acquisitionCost = $acquisitionCost;
        $this->status = $status;
        $this->depreciationMethod = $depreciationMethod;
        $this->usefulLifeYears = $usefulLifeYears;
        $this->salvageValue = $salvageValue;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    /**
     * Get the asset ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the tenant ID
     *
     * @return string
     */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * Get the asset name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the asset type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the serial number
     *
     * @return string|null
     */
    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    /**
     * Get the asset owner ID
     *
     * @return string
     */
    public function getAssetOwnerId(): string
    {
        return $this->assetOwnerId;
    }

    /**
     * Get the purchase date
     *
     * @return \DateTime
     */
    public function getPurchaseDate(): \DateTime
    {
        return $this->purchaseDate;
    }

    /**
     * Get the acquisition cost
     *
     * @return string
     */
    public function getAcquisitionCost(): string
    {
        return $this->acquisitionCost;
    }

    /**
     * Get the current status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Update the asset status
     *
     * @param string $newStatus
     * @return void
     */
    public function updateStatus(string $newStatus): void
    {
        $this->status = $newStatus;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get the depreciation method
     *
     * @return string
     */
    public function getDepreciationMethod(): string
    {
        return $this->depreciationMethod;
    }

    /**
     * Get the useful life in years
     *
     * @return int
     */
    public function getUsefulLifeYears(): int
    {
        return $this->usefulLifeYears;
    }

    /**
     * Get the salvage value
     *
     * @return string
     */
    public function getSalvageValue(): string
    {
        return $this->salvageValue;
    }

    /**
     * Get the creation timestamp
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Get the last update timestamp
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Get the deletion timestamp
     *
     * @return \DateTime|null
     */
    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    /**
     * Soft delete the asset
     *
     * @return void
     */
    public function delete(): void
    {
        $this->deletedAt = new \DateTime();
        $this->status = 'retired';
    }

    /**
     * Check if the asset is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
