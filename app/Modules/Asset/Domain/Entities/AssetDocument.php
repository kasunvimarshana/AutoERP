<?php declare(strict_types=1);

namespace Modules\Asset\Domain\Entities;

/**
 * AssetDocument Entity - Represents documents associated with assets
 *
 * Tracks documents like registration certificates, insurance documents,
 * inspection certificates, and other compliance documents with expiry dates.
 *
 * @package Modules\Asset\Domain\Entities
 */
final class AssetDocument
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
     * Document type
     *
     * Values: registration, insurance, inspection, roadworthiness, emission, tax, loan, title
     *
     * @var string
     */
    private string $documentType;

    /**
     * Document name/title
     *
     * @var string
     */
    private string $documentName;

    /**
     * Document number/reference
     *
     * @var string
     */
    private string $documentNumber;

    /**
     * Issue date
     *
     * @var \DateTime
     */
    private \DateTime $issueDate;

    /**
     * Expiry date
     *
     * @var \DateTime|null
     */
    private ?\DateTime $expiryDate;

    /**
     * File path/storage location
     *
     * @var string|null
     */
    private ?string $filePath;

    /**
     * File URL for access
     *
     * @var string|null
     */
    private ?string $fileUrl;

    /**
     * Issuing authority
     *
     * @var string|null
     */
    private ?string $issuingAuthority;

    /**
     * Notes/remarks
     *
     * @var string|null
     */
    private ?string $notes;

    /**
     * Is this document currently valid/active?
     *
     * @var bool
     */
    private bool $isActive;

    /**
     * Creation timestamp
     *
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Last update timestamp
     *
     * @var \DateTime|null
     */
    private ?\DateTime $updatedAt;

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
     * @param string $documentType
     * @param string $documentName
     * @param string $documentNumber
     * @param \DateTime $issueDate
     * @param \DateTime|null $expiryDate
     * @param string|null $filePath
     * @param string|null $fileUrl
     * @param string|null $issuingAuthority
     * @param string|null $notes
     * @param bool $isActive
     */
    public function __construct(
        string $id,
        string $tenantId,
        string $assetId,
        string $documentType,
        string $documentName,
        string $documentNumber,
        \DateTime $issueDate,
        ?\DateTime $expiryDate = null,
        ?string $filePath = null,
        ?string $fileUrl = null,
        ?string $issuingAuthority = null,
        ?string $notes = null,
        bool $isActive = true,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->assetId = $assetId;
        $this->documentType = $documentType;
        $this->documentName = $documentName;
        $this->documentNumber = $documentNumber;
        $this->issueDate = $issueDate;
        $this->expiryDate = $expiryDate;
        $this->filePath = $filePath;
        $this->fileUrl = $fileUrl;
        $this->issuingAuthority = $issuingAuthority;
        $this->notes = $notes;
        $this->isActive = $isActive;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    /**
     * Get document ID
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
     * Get document type
     *
     * @return string
     */
    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    /**
     * Get document name
     *
     * @return string
     */
    public function getDocumentName(): string
    {
        return $this->documentName;
    }

    /**
     * Get document number
     *
     * @return string
     */
    public function getDocumentNumber(): string
    {
        return $this->documentNumber;
    }

    /**
     * Get issue date
     *
     * @return \DateTime
     */
    public function getIssueDate(): \DateTime
    {
        return $this->issueDate;
    }

    /**
     * Get expiry date
     *
     * @return \DateTime|null
     */
    public function getExpiryDate(): ?\DateTime
    {
        return $this->expiryDate;
    }

    /**
     * Check if document is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }
        return $this->expiryDate < new \DateTime();
    }

    /**
     * Check if document expires soon (within 30 days)
     *
     * @param int $daysThreshold
     * @return bool
     */
    public function expiresWithin(int $daysThreshold = 30): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        $now = new \DateTime();
        $threshold = (clone $now)->modify("+{$daysThreshold} days");

        return $this->expiryDate <= $threshold && $this->expiryDate > $now;
    }

    /**
     * Get file path
     *
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * Update file information
     *
     * @param string|null $filePath
     * @param string|null $fileUrl
     * @return void
     */
    public function updateFile(?string $filePath = null, ?string $fileUrl = null): void
    {
        if ($filePath !== null) {
            $this->filePath = $filePath;
        }
        if ($fileUrl !== null) {
            $this->fileUrl = $fileUrl;
        }
        $this->updatedAt = new \DateTime();
    }

    /**
     * Renew/update the document
     *
     * @param \DateTime $newExpiryDate
     * @param string|null $newDocumentNumber
     * @return void
     */
    public function renew(\DateTime $newExpiryDate, ?string $newDocumentNumber = null): void
    {
        $this->issueDate = new \DateTime();
        $this->expiryDate = $newExpiryDate;
        if ($newDocumentNumber !== null) {
            $this->documentNumber = $newDocumentNumber;
        }
        $this->updatedAt = new \DateTime();
    }

    /**
     * Is document active?
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive && !$this->isExpired();
    }

    /**
     * Activate the document
     *
     * @return void
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Deactivate the document
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTime();
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
     * Soft delete
     *
     * @return void
     */
    public function delete(): void
    {
        $this->deletedAt = new \DateTime();
        $this->isActive = false;
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
