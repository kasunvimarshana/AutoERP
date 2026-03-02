<?php
declare(strict_types=1);
namespace Modules\Procurement\Domain\Entities;
class Vendor {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private string          $name,
        private ?string         $email,
        private ?string         $phone,
        private ?string         $address,
        private ?string         $taxNumber,
        private int             $paymentTerms,
        private string          $creditLimit,
        private string          $openingBalance,
        private bool            $isActive,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getAddress(): ?string { return $this->address; }
    public function getTaxNumber(): ?string { return $this->taxNumber; }
    public function getPaymentTerms(): int { return $this->paymentTerms; }
    public function getCreditLimit(): string { return $this->creditLimit; }
    public function getOpeningBalance(): string { return $this->openingBalance; }
    public function isActive(): bool { return $this->isActive; }
}
