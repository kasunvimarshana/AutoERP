<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

class Asset
{
    private ?int $id;

    public function __construct(
        private int $tenantId,
        private string $assetCode,
        private string $assetName,
        private string $usageMode,
        private string $lifecycleStatus = 'active',
        private string $rentalStatus = 'available',
        private string $serviceStatus = 'available',
        private ?int $orgUnitId = null,
        private ?int $productId = null,
        private ?int $serialId = null,
        private ?int $supplierId = null,
        private ?int $warehouseId = null,
        private ?int $currencyId = null,
        private ?int $createdBy = null,
        private ?string $registrationNumber = null,
        private ?string $chassisNumber = null,
        private ?string $engineNumber = null,
        private ?int $yearOfManufacture = null,
        private ?string $make = null,
        private ?string $model = null,
        private ?string $color = null,
        private ?string $fuelType = null,
        private ?string $purchaseCost = null,
        private ?string $bookValue = null,
        private ?\DateTimeInterface $purchaseDate = null,
        private ?string $currentOdometer = null,
        private ?string $engineHours = null,
        private ?string $notes = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getAssetCode(): string { return $this->assetCode; }
    public function getAssetName(): string { return $this->assetName; }
    public function getUsageMode(): string { return $this->usageMode; }
    public function getLifecycleStatus(): string { return $this->lifecycleStatus; }
    public function getRentalStatus(): string { return $this->rentalStatus; }
    public function getServiceStatus(): string { return $this->serviceStatus; }
    public function getProductId(): ?int { return $this->productId; }
    public function getSerialId(): ?int { return $this->serialId; }
    public function getSupplierId(): ?int { return $this->supplierId; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function getCurrencyId(): ?int { return $this->currencyId; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getRegistrationNumber(): ?string { return $this->registrationNumber; }
    public function getChassisNumber(): ?string { return $this->chassisNumber; }
    public function getEngineNumber(): ?string { return $this->engineNumber; }
    public function getYearOfManufacture(): ?int { return $this->yearOfManufacture; }
    public function getMake(): ?string { return $this->make; }
    public function getModel(): ?string { return $this->model; }
    public function getColor(): ?string { return $this->color; }
    public function getFuelType(): ?string { return $this->fuelType; }
    public function getPurchaseCost(): ?string { return $this->purchaseCost; }
    public function getBookValue(): ?string { return $this->bookValue; }
    public function getPurchaseDate(): ?\DateTimeInterface { return $this->purchaseDate; }
    public function getCurrentOdometer(): ?string { return $this->currentOdometer; }
    public function getEngineHours(): ?string { return $this->engineHours; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateRentalStatus(string $rentalStatus): void
    {
        $this->rentalStatus = $rentalStatus;
        $this->rowVersion++;
    }

    public function updateServiceStatus(string $serviceStatus): void
    {
        $this->serviceStatus = $serviceStatus;
        $this->rowVersion++;
    }

    public function update(array $fields): void
    {
        foreach ($fields as $field => $value) {
            if (property_exists($this, $field) && $field !== 'id' && $field !== 'tenantId') {
                $this->{$field} = $value;
            }
        }
        $this->rowVersion++;
    }
}
