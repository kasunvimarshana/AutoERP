<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;

/**
 * A CRM contact — can be an individual customer, supplier rep, or prospect.
 * type: person | organisation
 */
class Contact
{
    public const TYPE_PERSON       = 'person';
    public const TYPE_ORGANISATION = 'organisation';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $type,
        private string $firstName,
        private ?string $lastName,
        private ?string $company,
        private ?string $jobTitle,
        private ?string $email,
        private ?string $phone,
        private ?string $mobile,
        private ?string $address,
        private ?int $ownerId,       // user responsible
        private ?int $customerId,    // link to Customer module
        private ?int $supplierId,    // link to Supplier module
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getType(): string { return $this->type; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getFullName(): string { return trim($this->firstName . ' ' . ($this->lastName ?? '')); }
    public function getCompany(): ?string { return $this->company; }
    public function getJobTitle(): ?string { return $this->jobTitle; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getMobile(): ?string { return $this->mobile; }
    public function getAddress(): ?string { return $this->address; }
    public function getOwnerId(): ?int { return $this->ownerId; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getSupplierId(): ?int { return $this->supplierId; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }

    public function update(
        string $firstName, ?string $lastName, ?string $company,
        ?string $jobTitle, ?string $email, ?string $phone, ?string $mobile, ?string $address,
    ): void {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->company   = $company;
        $this->jobTitle  = $jobTitle;
        $this->email     = $email;
        $this->phone     = $phone;
        $this->mobile    = $mobile;
        $this->address   = $address;
    }
}
