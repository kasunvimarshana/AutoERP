<?php declare(strict_types=1);

namespace Modules\Driver\Domain\Entities;

/**
 * Driver Entity - Represents a driver who operates rental vehicles
 *
 * Tracks driver information, license details, availability, and commission terms.
 * Drivers can be employees or third-party contractors.
 *
 * @package Modules\Driver\Domain\Entities
 */
final class Driver
{
    /**
     * Unique identifier (UUID)
     */
    private string $id;

    /**
     * Tenant ID for multi-tenancy
     */
    private string $tenantId;

    /**
     * Reference to Employee module (if driver is an employee)
     */
    private ?string $employeeId;

    /**
     * First name
     */
    private string $firstName;

    /**
     * Last name
     */
    private string $lastName;

    /**
     * Email address
     */
    private string $email;

    /**
     * Phone number
     */
    private string $phone;

    /**
     * Date of birth
     */
    private \DateTime $dateOfBirth;

    /**
     * Driver type: employee or contractor
     */
    private string $driverType;

    /**
     * Current status
     */
    private string $status;

    /**
     * Base daily wage (for employees)
     */
    private string $baseDailyWage;

    /**
     * Commission percentage on rentals
     */
    private string $commissionPercentage;

    /**
     * Date when driver became active
     */
    private \DateTime $activeSince;

    /**
     * Date when driver was terminated (soft delete)
     */
    private ?\DateTime $activeUntil;

    /**
     * Creation timestamp
     */
    private \DateTime $createdAt;

    /**
     * Last update timestamp
     */
    private ?\DateTime $updatedAt;

    /**
     * Soft delete timestamp
     */
    private ?\DateTime $deletedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $tenantId,
        ?string $employeeId,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        \DateTime $dateOfBirth,
        string $driverType,
        string $status,
        string $baseDailyWage,
        string $commissionPercentage,
        \DateTime $activeSince,
        ?\DateTime $activeUntil = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->employeeId = $employeeId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->dateOfBirth = $dateOfBirth;
        $this->driverType = $driverType;
        $this->status = $status;
        $this->baseDailyWage = $baseDailyWage;
        $this->commissionPercentage = $commissionPercentage;
        $this->activeSince = $activeSince;
        $this->activeUntil = $activeUntil;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getEmployeeId(): ?string { return $this->employeeId; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return "{$this->firstName} {$this->lastName}"; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getDateOfBirth(): \DateTime { return $this->dateOfBirth; }
    public function getDriverType(): string { return $this->driverType; }
    public function getStatus(): string { return $this->status; }
    public function getBaseDailyWage(): string { return $this->baseDailyWage; }
    public function getCommissionPercentage(): string { return $this->commissionPercentage; }
    public function getActiveSince(): \DateTime { return $this->activeSince; }
    public function getActiveUntil(): ?\DateTime { return $this->activeUntil; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    public function isActive(): bool
    {
        $now = new \DateTime();
        return $this->activeSince <= $now && ($this->activeUntil === null || $this->activeUntil > $now);
    }

    public function updateStatus(string $newStatus): void
    {
        $this->status = $newStatus;
        $this->updatedAt = new \DateTime();
    }

    public function terminate(\DateTime $terminationDate): void
    {
        $this->activeUntil = $terminationDate;
        $this->status = 'terminated';
        $this->updatedAt = new \DateTime();
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTime();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
