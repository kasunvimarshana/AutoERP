<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Product
{
    private ?int $id;

    private int $tenantId;

    private ?int $categoryId;

    private ?int $brandId;

    private ?int $orgUnitId;

    private string $type;

    private string $name;

    private string $slug;

    private ?string $sku;

    private ?string $description;

    private int $baseUomId;

    private ?int $purchaseUomId;

    private ?int $salesUomId;

    private string $uomConversionFactor;

    private bool $isBatchTracked;

    private bool $isLotTracked;

    private bool $isSerialTracked;

    private string $valuationMethod;

    private ?string $standardCost;

    private ?int $incomeAccountId;

    private ?int $cogsAccountId;

    private ?int $inventoryAccountId;

    private ?int $expenseAccountId;

    private bool $isActive;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        int $tenantId,
        string $type,
        string $name,
        string $slug,
        int $baseUomId,
        ?int $categoryId = null,
        ?int $brandId = null,
        ?int $orgUnitId = null,
        ?string $sku = null,
        ?string $description = null,
        ?int $purchaseUomId = null,
        ?int $salesUomId = null,
        string $uomConversionFactor = '1',
        bool $isBatchTracked = false,
        bool $isLotTracked = false,
        bool $isSerialTracked = false,
        string $valuationMethod = 'fifo',
        ?string $standardCost = null,
        ?int $incomeAccountId = null,
        ?int $cogsAccountId = null,
        ?int $inventoryAccountId = null,
        ?int $expenseAccountId = null,
        bool $isActive = true,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->categoryId = $categoryId;
        $this->brandId = $brandId;
        $this->orgUnitId = $orgUnitId;
        $this->type = $type;
        $this->name = $name;
        $this->slug = $slug;
        $this->sku = $sku;
        $this->description = $description;
        $this->baseUomId = $baseUomId;
        $this->purchaseUomId = $purchaseUomId;
        $this->salesUomId = $salesUomId;
        $this->uomConversionFactor = $uomConversionFactor;
        $this->isBatchTracked = $isBatchTracked;
        $this->isLotTracked = $isLotTracked;
        $this->isSerialTracked = $isSerialTracked;
        $this->valuationMethod = $valuationMethod;
        $this->standardCost = $standardCost;
        $this->incomeAccountId = $incomeAccountId;
        $this->cogsAccountId = $cogsAccountId;
        $this->inventoryAccountId = $inventoryAccountId;
        $this->expenseAccountId = $expenseAccountId;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
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

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getBrandId(): ?int
    {
        return $this->brandId;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getBaseUomId(): int
    {
        return $this->baseUomId;
    }

    public function getPurchaseUomId(): ?int
    {
        return $this->purchaseUomId;
    }

    public function getSalesUomId(): ?int
    {
        return $this->salesUomId;
    }

    public function getUomConversionFactor(): string
    {
        return $this->uomConversionFactor;
    }

    public function isBatchTracked(): bool
    {
        return $this->isBatchTracked;
    }

    public function isLotTracked(): bool
    {
        return $this->isLotTracked;
    }

    public function isSerialTracked(): bool
    {
        return $this->isSerialTracked;
    }

    public function getValuationMethod(): string
    {
        return $this->valuationMethod;
    }

    public function getStandardCost(): ?string
    {
        return $this->standardCost;
    }

    public function getIncomeAccountId(): ?int
    {
        return $this->incomeAccountId;
    }

    public function getCogsAccountId(): ?int
    {
        return $this->cogsAccountId;
    }

    public function getInventoryAccountId(): ?int
    {
        return $this->inventoryAccountId;
    }

    public function getExpenseAccountId(): ?int
    {
        return $this->expenseAccountId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
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
     * @param array<string, mixed>|null $metadata
     */
    public function update(
        string $type,
        string $name,
        string $slug,
        int $baseUomId,
        ?int $categoryId,
        ?int $brandId,
        ?int $orgUnitId,
        ?string $sku,
        ?string $description,
        ?int $purchaseUomId,
        ?int $salesUomId,
        string $uomConversionFactor,
        bool $isBatchTracked,
        bool $isLotTracked,
        bool $isSerialTracked,
        string $valuationMethod,
        ?string $standardCost,
        ?int $incomeAccountId,
        ?int $cogsAccountId,
        ?int $inventoryAccountId,
        ?int $expenseAccountId,
        bool $isActive,
        ?array $metadata,
    ): void {
        $this->type = $type;
        $this->name = $name;
        $this->slug = $slug;
        $this->baseUomId = $baseUomId;
        $this->categoryId = $categoryId;
        $this->brandId = $brandId;
        $this->orgUnitId = $orgUnitId;
        $this->sku = $sku;
        $this->description = $description;
        $this->purchaseUomId = $purchaseUomId;
        $this->salesUomId = $salesUomId;
        $this->uomConversionFactor = $uomConversionFactor;
        $this->isBatchTracked = $isBatchTracked;
        $this->isLotTracked = $isLotTracked;
        $this->isSerialTracked = $isSerialTracked;
        $this->valuationMethod = $valuationMethod;
        $this->standardCost = $standardCost;
        $this->incomeAccountId = $incomeAccountId;
        $this->cogsAccountId = $cogsAccountId;
        $this->inventoryAccountId = $inventoryAccountId;
        $this->expenseAccountId = $expenseAccountId;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
