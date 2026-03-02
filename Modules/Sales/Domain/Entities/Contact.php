<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

use Modules\Sales\Domain\Enums\ContactType;

/**
 * Customer / Supplier contact.
 * Based on the Contact model from the PHP_POS reference (app/Contact.php).
 */
class Contact
{
    public function __construct(
        private readonly int         $id,
        private readonly int         $tenantId,
        private readonly ContactType $type,
        private readonly string      $name,
        private readonly ?string     $email,
        private readonly ?string     $phone,
        private readonly ?string     $taxNumber,
        private readonly ?string     $openingBalance,
        private readonly bool        $isActive,
    ) {}

    public function getId(): int              { return $this->id; }
    public function getTenantId(): int        { return $this->tenantId; }
    public function getType(): ContactType    { return $this->type; }
    public function getName(): string         { return $this->name; }
    public function getEmail(): ?string       { return $this->email; }
    public function getPhone(): ?string       { return $this->phone; }
    public function getTaxNumber(): ?string   { return $this->taxNumber; }
    public function getOpeningBalance(): ?string { return $this->openingBalance; }
    public function isActive(): bool          { return $this->isActive; }

    public function isCustomer(): bool
    {
        return $this->type === ContactType::CUSTOMER || $this->type === ContactType::BOTH;
    }

    public function isSupplier(): bool
    {
        return $this->type === ContactType::SUPPLIER || $this->type === ContactType::BOTH;
    }
}
