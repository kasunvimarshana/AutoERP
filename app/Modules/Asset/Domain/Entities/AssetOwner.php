<?php declare(strict_types=1);

namespace Modules\Asset\Domain\Entities;

/**
 * AssetOwner Entity - Represents owner of assets (company-owned or third-party)
 *
 * Tracks who owns each asset. Can be the company itself or a third-party owner,
 * with commission terms for third-party vehicles.
 *
 * @package Modules\Asset\Domain\Entities
 */
final class AssetOwner
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
     * Owner name
     *
     * @var string
     */
    private string $name;

    /**
     * Owner type
     *
     * Values: company (internal), third_party
     *
     * @var string
     */
    private string $ownerType;

    /**
     * Contact person name
     *
     * @var string|null
     */
    private ?string $contactPerson;

    /**
     * Email address
     *
     * @var string|null
     */
    private ?string $email;

    /**
     * Phone number
     *
     * @var string|null
     */
    private ?string $phone;

    /**
     * Address
     *
     * @var string|null
     */
    private ?string $address;

    /**
     * City
     *
     * @var string|null
     */
    private ?string $city;

    /**
     * State/Province
     *
     * @var string|null
     */
    private ?string $state;

    /**
     * Postal code
     *
     * @var string|null
     */
    private ?string $postalCode;

    /**
     * Country
     *
     * @var string|null
     */
    private ?string $country;

    /**
     * Tax ID / GST ID
     *
     * @var string|null
     */
    private ?string $taxId;

    /**
     * Commission percentage for third-party owners
     *
     * Values: 0.00 to 100.00
     *
     * @var string (DECIMAL for precision)
     */
    private string $commissionPercentage;

    /**
     * Payment terms (days)
     *
     * How many days after month-end the owner is paid
     *
     * @var int
     */
    private int $paymentTermsDays;

    /**
     * Is this owner active?
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
     * @param string $name
     * @param string $ownerType
     * @param string|null $contactPerson
     * @param string|null $email
     * @param string|null $phone
     * @param string|null $address
     * @param string|null $city
     * @param string|null $state
     * @param string|null $postalCode
     * @param string|null $country
     * @param string|null $taxId
     * @param string $commissionPercentage
     * @param int $paymentTermsDays
     * @param bool $isActive
     */
    public function __construct(
        string $id,
        string $tenantId,
        string $name,
        string $ownerType,
        ?string $contactPerson = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null,
        ?string $country = null,
        ?string $taxId = null,
        string $commissionPercentage = '0.00',
        int $paymentTermsDays = 30,
        bool $isActive = true,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->ownerType = $ownerType;
        $this->contactPerson = $contactPerson;
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->country = $country;
        $this->taxId = $taxId;
        $this->commissionPercentage = $commissionPercentage;
        $this->paymentTermsDays = $paymentTermsDays;
        $this->isActive = $isActive;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    /**
     * Get owner ID
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
     * Get owner name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get owner type
     *
     * @return string
     */
    public function getOwnerType(): string
    {
        return $this->ownerType;
    }

    /**
     * Is this a company-owned asset?
     *
     * @return bool
     */
    public function isCompanyOwned(): bool
    {
        return $this->ownerType === 'company';
    }

    /**
     * Is this a third-party owned asset?
     *
     * @return bool
     */
    public function isThirdPartyOwned(): bool
    {
        return $this->ownerType === 'third_party';
    }

    /**
     * Get commission percentage
     *
     * @return string
     */
    public function getCommissionPercentage(): string
    {
        return $this->commissionPercentage;
    }

    /**
     * Update commission percentage
     *
     * @param string $newPercentage
     * @return void
     * @throws \DomainException if percentage is invalid
     */
    public function updateCommissionPercentage(string $newPercentage): void
    {
        $percentage = (float)$newPercentage;
        if ($percentage < 0 || $percentage > 100) {
            throw new \DomainException('Commission percentage must be between 0 and 100');
        }
        $this->commissionPercentage = $newPercentage;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get payment terms (days)
     *
     * @return int
     */
    public function getPaymentTermsDays(): int
    {
        return $this->paymentTermsDays;
    }

    /**
     * Update payment terms
     *
     * @param int $days
     * @return void
     */
    public function updatePaymentTerms(int $days): void
    {
        $this->paymentTermsDays = $days;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Is this owner active?
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Activate the owner
     *
     * @return void
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Deactivate the owner
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
