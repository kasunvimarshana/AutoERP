<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Entities;

class Supplier
{
    private ?int $id;

    private int $tenantId;

    private int $userId;

    private ?string $supplierCode;

    private string $name;

    private string $type;

    private ?int $orgUnitId;

    private ?string $taxNumber;

    private ?string $registrationNumber;

    private ?int $currencyId;

    private int $paymentTermsDays;

    private ?int $apAccountId;

    private string $status;

    private ?string $notes;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private int $rowVersion;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        int $userId,
        string $name,
        string $type = 'company',
        ?string $supplierCode = null,
        ?int $orgUnitId = null,
        ?string $taxNumber = null,
        ?string $registrationNumber = null,
        ?int $currencyId = null,
        int $paymentTermsDays = 30,
        ?int $apAccountId = null,
        string $status = 'active',
        ?string $notes = null,
        ?array $metadata = null,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertType($type);
        $this->assertStatus($status);
        $this->assertPaymentTermsDays($paymentTermsDays);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->supplierCode = $supplierCode;
        $this->name = $name;
        $this->type = $type;
        $this->orgUnitId = $orgUnitId;
        $this->taxNumber = $taxNumber;
        $this->registrationNumber = $registrationNumber;
        $this->currencyId = $currencyId;
        $this->paymentTermsDays = $paymentTermsDays;
        $this->apAccountId = $apAccountId;
        $this->status = $status;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->rowVersion = $rowVersion;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSupplierCode(): ?string
    {
        return $this->supplierCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function getCurrencyId(): ?int
    {
        return $this->currencyId;
    }

    public function getPaymentTermsDays(): int
    {
        return $this->paymentTermsDays;
    }

    public function getApAccountId(): ?int
    {
        return $this->apAccountId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        int $userId,
        ?string $supplierCode,
        string $name,
        string $type,
        ?int $orgUnitId,
        ?string $taxNumber,
        ?string $registrationNumber,
        ?int $currencyId,
        int $paymentTermsDays,
        ?int $apAccountId,
        string $status,
        ?string $notes,
        ?array $metadata,
    ): void {
        $this->assertType($type);
        $this->assertStatus($status);
        $this->assertPaymentTermsDays($paymentTermsDays);

        $this->userId = $userId;
        $this->supplierCode = $supplierCode;
        $this->name = $name;
        $this->type = $type;
        $this->orgUnitId = $orgUnitId;
        $this->taxNumber = $taxNumber;
        $this->registrationNumber = $registrationNumber;
        $this->currencyId = $currencyId;
        $this->paymentTermsDays = $paymentTermsDays;
        $this->apAccountId = $apAccountId;
        $this->status = $status;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['individual', 'company'], true)) {
            throw new \InvalidArgumentException('Supplier type must be either individual or company.');
        }
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException('Supplier status must be active or inactive.');
        }
    }

    private function assertPaymentTermsDays(int $paymentTermsDays): void
    {
        if ($paymentTermsDays < 0) {
            throw new \InvalidArgumentException('Payment terms days cannot be negative.');
        }
    }
}
