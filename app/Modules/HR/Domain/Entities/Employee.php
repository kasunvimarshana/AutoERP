<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\PhoneNumber;

class Employee
{
    private ?int $id;

    private int $tenantId;

    private Name $firstName;

    private Name $lastName;

    private Email $email;

    private ?PhoneNumber $phone;

    private ?string $dateOfBirth;

    private ?string $gender;

    private ?string $address;

    private Code $employeeNumber;

    private \DateTimeInterface $hireDate;

    private string $employmentType;

    private string $status;

    private ?int $departmentId;

    private ?int $positionId;

    private ?int $managerId;

    private ?float $salary;

    private string $currency;

    private ?int $orgUnitId;

    private Metadata $metadata;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    private ?int $userId = null;

    public function __construct(
        int $tenantId,
        Name $firstName,
        Name $lastName,
        Email $email,
        Code $employeeNumber,
        \DateTimeInterface $hireDate,
        string $employmentType,
        string $status = 'active',
        ?PhoneNumber $phone = null,
        ?string $dateOfBirth = null,
        ?string $gender = null,
        ?string $address = null,
        ?int $departmentId = null,
        ?int $positionId = null,
        ?int $managerId = null,
        ?float $salary = null,
        string $currency = 'USD',
        ?int $orgUnitId = null,
        ?Metadata $metadata = null,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?int $userId = null
    ) {
        $this->id             = $id;
        $this->tenantId       = $tenantId;
        $this->firstName      = $firstName;
        $this->lastName       = $lastName;
        $this->email          = $email;
        $this->phone          = $phone;
        $this->dateOfBirth    = $dateOfBirth;
        $this->gender         = $gender;
        $this->address        = $address;
        $this->employeeNumber = $employeeNumber;
        $this->hireDate       = $hireDate;
        $this->employmentType = $employmentType;
        $this->status         = $status;
        $this->departmentId   = $departmentId;
        $this->positionId     = $positionId;
        $this->managerId      = $managerId;
        $this->salary         = $salary;
        $this->currency       = $currency;
        $this->orgUnitId      = $orgUnitId;
        $this->metadata       = $metadata ?? new Metadata([]);
        $this->isActive       = $isActive;
        $this->createdAt      = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt      = $updatedAt ?? new \DateTimeImmutable;
        $this->userId         = $userId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getFirstName(): Name
    {
        return $this->firstName;
    }

    public function getLastName(): Name
    {
        return $this->lastName;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    public function getDateOfBirth(): ?string
    {
        return $this->dateOfBirth;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getEmployeeNumber(): Code
    {
        return $this->employeeNumber;
    }

    public function getHireDate(): \DateTimeInterface
    {
        return $this->hireDate;
    }

    public function getEmploymentType(): string
    {
        return $this->employmentType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function getPositionId(): ?int
    {
        return $this->positionId;
    }

    public function getManagerId(): ?int
    {
        return $this->managerId;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
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

    public function updateDetails(
        Name $firstName,
        Name $lastName,
        Email $email,
        ?PhoneNumber $phone,
        ?string $dateOfBirth,
        ?string $gender,
        ?string $address,
        Code $employeeNumber,
        \DateTimeInterface $hireDate,
        string $employmentType,
        string $status,
        ?int $departmentId,
        ?int $positionId,
        ?int $managerId,
        ?float $salary,
        string $currency,
        ?int $orgUnitId,
        ?Metadata $metadata,
        bool $isActive,
        ?int $userId = null
    ): void {
        $this->firstName      = $firstName;
        $this->lastName       = $lastName;
        $this->email          = $email;
        $this->phone          = $phone;
        $this->dateOfBirth    = $dateOfBirth;
        $this->gender         = $gender;
        $this->address        = $address;
        $this->employeeNumber = $employeeNumber;
        $this->hireDate       = $hireDate;
        $this->employmentType = $employmentType;
        $this->status         = $status;
        $this->departmentId   = $departmentId;
        $this->positionId     = $positionId;
        $this->managerId      = $managerId;
        $this->salary         = $salary;
        $this->currency       = $currency;
        $this->orgUnitId      = $orgUnitId;
        $this->metadata       = $metadata ?? new Metadata([]);
        $this->isActive       = $isActive;
        $this->userId         = $userId;
        $this->updatedAt      = new \DateTimeImmutable;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function linkToUser(?int $userId): void
    {
        $this->userId    = $userId;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function terminate(): void
    {
        $this->status    = 'terminated';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
