<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Entities;

class Supplier
{
    private ?int $id;
    private int $tenantId;
    private ?int $userId;
    private string $name;
    private string $code;
    private ?string $email;
    private ?string $phone;
    private ?array $address;
    private ?array $contactPerson;
    private ?string $paymentTerms;
    private string $currency;
    private ?string $taxNumber;
    private string $status;
    private string $type;
    private ?array $attributes;
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $code,
        ?int $userId = null,
        ?string $email = null,
        ?string $phone = null,
        ?array $address = null,
        ?array $contactPerson = null,
        ?string $paymentTerms = null,
        string $currency = 'USD',
        ?string $taxNumber = null,
        string $status = 'active',
        string $type = 'other',
        ?array $attributes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->name = $name;
        $this->code = $code;
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
        $this->contactPerson = $contactPerson;
        $this->paymentTerms = $paymentTerms;
        $this->currency = $currency;
        $this->taxNumber = $taxNumber;
        $this->status = $status;
        $this->type = $type;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getUserId(): ?int { return $this->userId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getAddress(): ?array { return $this->address; }
    public function getContactPerson(): ?array { return $this->contactPerson; }
    public function getPaymentTerms(): ?string { return $this->paymentTerms; }
    public function getCurrency(): string { return $this->currency; }
    public function getTaxNumber(): ?string { return $this->taxNumber; }
    public function getStatus(): string { return $this->status; }
    public function getType(): string { return $this->type; }
    public function getAttributes(): ?array { return $this->attributes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        string $name,
        string $code,
        ?int $userId,
        ?string $email,
        ?string $phone,
        ?array $address,
        ?array $contactPerson,
        ?string $paymentTerms,
        string $currency,
        ?string $taxNumber,
        string $type,
        ?array $attributes,
        ?array $metadata
    ): void {
        $this->name = $name;
        $this->code = $code;
        $this->userId = $userId;
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
        $this->contactPerson = $contactPerson;
        $this->paymentTerms = $paymentTerms;
        $this->currency = $currency;
        $this->taxNumber = $taxNumber;
        $this->type = $type;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasUserAccess(): bool
    {
        return $this->userId !== null;
    }
}
