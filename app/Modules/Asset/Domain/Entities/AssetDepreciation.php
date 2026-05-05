<?php declare(strict_types=1);

namespace Modules\Asset\Domain\Entities;

/**
 * AssetDepreciation Entity - Tracks depreciation calculations and GL entries
 *
 * Maintains depreciation schedule for assets, calculating book value,
 * accumulated depreciation, and generating GL entries for each period.
 *
 * @package Modules\Asset\Domain\Entities
 */
final class AssetDepreciation
{
    /**
     * Unique identifier (UUID)
     *
     * @var string
     */
    private string $id;

    /**
     * Tenant ID for multi-tenancy
     *
     * @var string
     */
    private string $tenantId;

    /**
     * Reference to the Asset
     *
     * @var string (UUID)
     */
    private string $assetId;

    /**
     * Depreciation period year
     *
     * @var int
     */
    private int $year;

    /**
     * Depreciation period month (1-12)
     *
     * @var int
     */
    private int $month;

    /**
     * Original acquisition cost
     *
     * @var string (DECIMAL for precision)
     */
    private string $originalCost;

    /**
     * Salvage value
     *
     * @var string (DECIMAL)
     */
    private string $salvageValue;

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
     * Depreciation for this period
     *
     * @var string (DECIMAL)
     */
    private string $depreciationAmount;

    /**
     * Accumulated depreciation up to this period
     *
     * @var string (DECIMAL)
     */
    private string $accumulatedDepreciation;

    /**
     * Book value at end of this period
     *
     * @var string (DECIMAL)
     */
    private string $bookValue;

    /**
     * Reference to GL Journal Entry created for this depreciation
     *
     * @var string|null (UUID reference to Finance.JournalEntry)
     */
    private ?string $journalEntryId;

    /**
     * Status of GL posting
     *
     * Values: pending, posted, reversed
     *
     * @var string
     */
    private string $postingStatus;

    /**
     * Creation timestamp
     *
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * GL posting timestamp
     *
     * @var \DateTime|null
     */
    private ?\DateTime $postedAt;

    /**
     * Soft delete timestamp
     *
     * @var \DateTime|null
     */
    private ?\DateTime $deletedAt;

    /**
     * Constructor
     *
     * @param string $id
     * @param string $tenantId
     * @param string $assetId
     * @param int $year
     * @param int $month
     * @param string $originalCost
     * @param string $salvageValue
     * @param string $depreciationMethod
     * @param int $usefulLifeYears
     * @param string $depreciationAmount
     * @param string $accumulatedDepreciation
     * @param string $bookValue
     * @param string|null $journalEntryId
     * @param string $postingStatus
     */
    public function __construct(
        string $id,
        string $tenantId,
        string $assetId,
        int $year,
        int $month,
        string $originalCost,
        string $salvageValue,
        string $depreciationMethod,
        int $usefulLifeYears,
        string $depreciationAmount,
        string $accumulatedDepreciation,
        string $bookValue,
        ?string $journalEntryId = null,
        string $postingStatus = 'pending',
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->assetId = $assetId;
        $this->year = $year;
        $this->month = $month;
        $this->originalCost = $originalCost;
        $this->salvageValue = $salvageValue;
        $this->depreciationMethod = $depreciationMethod;
        $this->usefulLifeYears = $usefulLifeYears;
        $this->depreciationAmount = $depreciationAmount;
        $this->accumulatedDepreciation = $accumulatedDepreciation;
        $this->bookValue = $bookValue;
        $this->journalEntryId = $journalEntryId;
        $this->postingStatus = $postingStatus;
        $this->createdAt = new \DateTime();
        $this->postedAt = null;
        $this->deletedAt = null;
    }

    /**
     * Get depreciation ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get tenant ID
     *
     * @return string
     */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * Get asset ID
     *
     * @return string
     */
    public function getAssetId(): string
    {
        return $this->assetId;
    }

    /**
     * Get year
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get month
     *
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * Get depreciation amount for this period
     *
     * @return string
     */
    public function getDepreciationAmount(): string
    {
        return $this->depreciationAmount;
    }

    /**
     * Get accumulated depreciation
     *
     * @return string
     */
    public function getAccumulatedDepreciation(): string
    {
        return $this->accumulatedDepreciation;
    }

    /**
     * Get book value
     *
     * @return string
     */
    public function getBookValue(): string
    {
        return $this->bookValue;
    }

    /**
     * Get journal entry ID
     *
     * @return string|null
     */
    public function getJournalEntryId(): ?string
    {
        return $this->journalEntryId;
    }

    /**
     * Link to a journal entry
     *
     * @param string $journalEntryId
     * @return void
     */
    public function linkToJournalEntry(string $journalEntryId): void
    {
        $this->journalEntryId = $journalEntryId;
    }

    /**
     * Get posting status
     *
     * @return string
     */
    public function getPostingStatus(): string
    {
        return $this->postingStatus;
    }

    /**
     * Mark as posted
     *
     * @return void
     */
    public function markAsPosted(): void
    {
        $this->postingStatus = 'posted';
        $this->postedAt = new \DateTime();
    }

    /**
     * Mark as pending
     *
     * @return void
     */
    public function markAsPending(): void
    {
        $this->postingStatus = 'pending';
        $this->postedAt = null;
    }

    /**
     * Reverse the depreciation entry
     *
     * @return void
     */
    public function reverse(): void
    {
        $this->postingStatus = 'reversed';
    }

    /**
     * Is posting pending?
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->postingStatus === 'pending';
    }

    /**
     * Is posting completed?
     *
     * @return bool
     */
    public function isPosted(): bool
    {
        return $this->postingStatus === 'posted';
    }

    /**
     * Get creation timestamp
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Get posting timestamp
     *
     * @return \DateTime|null
     */
    public function getPostedAt(): ?\DateTime
    {
        return $this->postedAt;
    }

    /**
     * Soft delete
     *
     * @return void
     */
    public function delete(): void
    {
        $this->deletedAt = new \DateTime();
    }

    /**
     * Check if deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
