<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Entities;

class Employee
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_ON_LEAVE = 'on_leave';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private ?int $userId,
        private int $departmentId,
        private int $positionId,
        private string $employeeCode,
        private string $firstName,
        private string $lastName,
        private string $email,
        private ?string $phone,
        private ?string $gender,
        private ?\DateTimeInterface $dateOfBirth,
        private \DateTimeInterface $hireDate,
        private ?\DateTimeInterface $terminationDate,
        private string $status,
        private ?float $baseSalary,
        private ?string $bankAccount,
        private ?string $taxId,
        private ?string $address,
        private ?string $emergencyContactName,
        private ?string $emergencyContactPhone,
        private ?array $metadata,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getUserId(): ?int { return $this->userId; }
    public function getDepartmentId(): int { return $this->departmentId; }
    public function getPositionId(): int { return $this->positionId; }
    public function getEmployeeCode(): string { return $this->employeeCode; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getGender(): ?string { return $this->gender; }
    public function getDateOfBirth(): ?\DateTimeInterface { return $this->dateOfBirth; }
    public function getHireDate(): \DateTimeInterface { return $this->hireDate; }
    public function getTerminationDate(): ?\DateTimeInterface { return $this->terminationDate; }
    public function getStatus(): string { return $this->status; }
    public function getBaseSalary(): ?float { return $this->baseSalary; }
    public function getBankAccount(): ?string { return $this->bankAccount; }
    public function getTaxId(): ?string { return $this->taxId; }
    public function getAddress(): ?string { return $this->address; }
    public function getEmergencyContactName(): ?string { return $this->emergencyContactName; }
    public function getEmergencyContactPhone(): ?string { return $this->emergencyContactPhone; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function isTerminated(): bool { return $this->status === self::STATUS_TERMINATED; }
    public function isOnLeave(): bool { return $this->status === self::STATUS_ON_LEAVE; }

    public function updateProfile(
        string $firstName,
        string $lastName,
        ?string $phone,
        ?string $gender,
        ?\DateTimeInterface $dateOfBirth,
        ?string $address,
        ?string $emergencyContactName,
        ?string $emergencyContactPhone
    ): void {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->gender = $gender;
        $this->dateOfBirth = $dateOfBirth;
        $this->address = $address;
        $this->emergencyContactName = $emergencyContactName;
        $this->emergencyContactPhone = $emergencyContactPhone;
    }

    public function transfer(int $departmentId, int $positionId): void
    {
        $this->departmentId = $departmentId;
        $this->positionId = $positionId;
    }

    public function updateSalary(float $baseSalary): void
    {
        $this->baseSalary = $baseSalary;
    }

    public function terminate(\DateTimeInterface $terminationDate): void
    {
        $this->status = self::STATUS_TERMINATED;
        $this->terminationDate = $terminationDate;
    }

    public function setOnLeave(): void { $this->status = self::STATUS_ON_LEAVE; }
    public function returnFromLeave(): void { $this->status = self::STATUS_ACTIVE; }
    public function activate(): void { $this->status = self::STATUS_ACTIVE; }
    public function deactivate(): void { $this->status = self::STATUS_INACTIVE; }

    public function linkUser(int $userId): void { $this->userId = $userId; }
}
