<?php declare(strict_types=1);

namespace Modules\Driver\Domain\Entities;

/**
 * License Entity - Represents a driving license
 *
 * Tracks license number, expiry date, class/category, and compliance status.
 */
final class License
{
    private string $id;
    private string $tenantId;
    private string $driverId;
    private string $licenseNumber;
    private string $licenseClass;
    private \DateTime $issueDate;
    private \DateTime $expiryDate;
    private ?string $issuingAuthority;
    private bool $isActive;
    private \DateTime $createdAt;
    private ?\DateTime $updatedAt;
    private ?\DateTime $deletedAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $driverId,
        string $licenseNumber,
        string $licenseClass,
        \DateTime $issueDate,
        \DateTime $expiryDate,
        ?string $issuingAuthority = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->driverId = $driverId;
        $this->licenseNumber = $licenseNumber;
        $this->licenseClass = $licenseClass;
        $this->issueDate = $issueDate;
        $this->expiryDate = $expiryDate;
        $this->issuingAuthority = $issuingAuthority;
        $this->isActive = true;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getDriverId(): string { return $this->driverId; }
    public function getLicenseNumber(): string { return $this->licenseNumber; }
    public function getLicenseClass(): string { return $this->licenseClass; }
    public function getIssueDate(): \DateTime { return $this->issueDate; }
    public function getExpiryDate(): \DateTime { return $this->expiryDate; }
    public function isActive(): bool { return $this->isActive && !$this->isExpired(); }

    public function isExpired(): bool
    {
        return $this->expiryDate < new \DateTime();
    }

    public function expiresWithin(int $days = 30): bool
    {
        $now = new \DateTime();
        $threshold = (clone $now)->modify("+{$days} days");
        return $this->expiryDate <= $threshold && $this->expiryDate > $now;
    }

    public function renew(\DateTime $newExpiryDate): void
    {
        $this->issueDate = new \DateTime();
        $this->expiryDate = $newExpiryDate;
        $this->isActive = true;
        $this->updatedAt = new \DateTime();
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTime();
        $this->isActive = false;
    }
}
