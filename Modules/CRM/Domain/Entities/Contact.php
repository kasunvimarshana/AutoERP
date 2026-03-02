<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;
class Contact {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private string          $firstName,
        private string          $lastName,
        private ?string         $email,
        private ?string         $phone,
        private ?string         $mobile,
        private ?string         $companyName,
        private string          $type,
        private string          $creditLimit,
        private string          $openingBalance,
        private ?string         $taxNumber,
        private ?string         $address,
        private ?string         $city,
        private ?string         $state,
        private ?string         $country,
        private bool            $isActive,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return trim($this->firstName.' '.$this->lastName); }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getMobile(): ?string { return $this->mobile; }
    public function getCompanyName(): ?string { return $this->companyName; }
    public function getType(): string { return $this->type; }
    public function getCreditLimit(): string { return $this->creditLimit; }
    public function getOpeningBalance(): string { return $this->openingBalance; }
    public function getTaxNumber(): ?string { return $this->taxNumber; }
    public function getAddress(): ?string { return $this->address; }
    public function getCity(): ?string { return $this->city; }
    public function getState(): ?string { return $this->state; }
    public function getCountry(): ?string { return $this->country; }
    public function isActive(): bool { return $this->isActive; }
}
