<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Core\Domain\ValueObjects\TenantId;
use Modules\Product\Domain\ValueObjects\ProductStatus;
use Modules\Product\Domain\ValueObjects\ProductType;
use Modules\Product\Domain\ValueObjects\UnitOfMeasure;

/**
 * Product Domain Entity
 *
 * Pure PHP — no Eloquent, no framework dependencies.
 * Confirmed pattern from KVAutoERP PR #37.
 *
 * Supports all product types: physical, service, digital, subscription,
 * combo, variable, raw_material, finished_good, wip, kit.
 */
final class Product
{
    private ?int $id;
    private TenantId $tenantId;
    private Sku $sku;
    private string $name;
    private Money $price;
    private ProductType $type;
    private ProductStatus $status;
    private ?string $description;
    private ?string $category;

    /** @var UnitOfMeasure[] */
    private array $unitsOfMeasure;

    // Tracking flags
    private bool $trackBatches;
    private bool $trackLots;
    private bool $trackSerials;
    private bool $trackExpiry;

    // Reorder
    private ?float $reorderPoint;
    private ?float $safetyStock;
    private ?int   $leadTimeDays;

    // Standard cost (for standard_cost valuation method)
    private ?float $standardCost;

    // Digital product config
    private ?string $downloadUrl;
    private ?int    $downloadLimit;
    private ?int    $downloadExpiryDays;

    // Subscription config
    private ?string $subscriptionInterval;
    private ?int    $subscriptionIntervalCount;

    // Flexible bags for extensibility (confirmed from PR #37)
    private ?array $attributes;
    private ?array $metadata;

    private DateTimeInterface $createdAt;
    private DateTimeInterface $updatedAt;

    public function __construct(
        int          $tenantId,
        string       $sku,
        string       $name,
        float        $price,
        string       $currency    = 'USD',
        string       $type        = ProductType::PHYSICAL,
        string       $status      = ProductStatus::ACTIVE,
        ?string      $description = null,
        ?string      $category    = null,
        array        $unitsOfMeasure = [],
        bool         $trackBatches   = false,
        bool         $trackLots      = false,
        bool         $trackSerials   = false,
        bool         $trackExpiry    = false,
        ?float       $reorderPoint   = null,
        ?float       $safetyStock    = null,
        ?int         $leadTimeDays   = null,
        ?float       $standardCost   = null,
        ?string      $downloadUrl    = null,
        ?int         $downloadLimit  = null,
        ?int         $downloadExpiryDays = null,
        ?string      $subscriptionInterval = null,
        ?int         $subscriptionIntervalCount = null,
        ?array       $attributes     = null,
        ?array       $metadata       = null,
        ?int         $id             = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null,
    ) {
        $this->id             = $id;
        $this->tenantId       = new TenantId($tenantId);
        $this->sku            = new Sku($sku);
        $this->name           = $name;
        $this->price          = new Money($price, $currency);
        $this->type           = new ProductType($type);
        $this->status         = new ProductStatus($status);
        $this->description    = $description;
        $this->category       = $category;
        $this->unitsOfMeasure = array_map(
            fn ($u) => $u instanceof UnitOfMeasure ? $u : UnitOfMeasure::fromArray($u),
            $unitsOfMeasure
        );
        $this->trackBatches   = $trackBatches;
        $this->trackLots      = $trackLots;
        $this->trackSerials   = $trackSerials;
        $this->trackExpiry    = $trackExpiry;
        $this->reorderPoint   = $reorderPoint;
        $this->safetyStock    = $safetyStock;
        $this->leadTimeDays   = $leadTimeDays;
        $this->standardCost   = $standardCost;
        $this->downloadUrl    = $downloadUrl;
        $this->downloadLimit  = $downloadLimit;
        $this->downloadExpiryDays = $downloadExpiryDays;
        $this->subscriptionInterval = $subscriptionInterval;
        $this->subscriptionIntervalCount = $subscriptionIntervalCount;
        $this->attributes     = $attributes;
        $this->metadata       = $metadata;
        $this->createdAt      = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt      = $updatedAt ?? new DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────────────────────────────
    public function getId(): ?int              { return $this->id; }
    public function getTenantId(): TenantId    { return $this->tenantId; }
    public function getSku(): Sku              { return $this->sku; }
    public function getName(): string          { return $this->name; }
    public function getPrice(): Money          { return $this->price; }
    public function getType(): ProductType     { return $this->type; }
    public function getStatus(): ProductStatus { return $this->status; }
    public function getDescription(): ?string  { return $this->description; }
    public function getCategory(): ?string     { return $this->category; }
    public function getAttributes(): ?array    { return $this->attributes; }
    public function getMetadata(): ?array      { return $this->metadata; }
    public function isTrackBatches(): bool     { return $this->trackBatches; }
    public function isTrackLots(): bool        { return $this->trackLots; }
    public function isTrackSerials(): bool     { return $this->trackSerials; }
    public function isTrackExpiry(): bool      { return $this->trackExpiry; }
    public function getReorderPoint(): ?float  { return $this->reorderPoint; }
    public function getSafetyStock(): ?float   { return $this->safetyStock; }
    public function getLeadTimeDays(): ?int    { return $this->leadTimeDays; }
    public function getStandardCost(): ?float  { return $this->standardCost; }
    public function getDownloadUrl(): ?string  { return $this->downloadUrl; }
    public function getDownloadLimit(): ?int   { return $this->downloadLimit; }
    public function getSubscriptionInterval(): ?string { return $this->subscriptionInterval; }
    public function getCreatedAt(): DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeInterface { return $this->updatedAt; }

    /** @return UnitOfMeasure[] */
    public function getUnitsOfMeasure(): array { return $this->unitsOfMeasure; }

    public function getBuyingUnit(): ?UnitOfMeasure
    {
        foreach ($this->unitsOfMeasure as $uom) {
            if ($uom->isBuying()) return $uom;
        }
        return null;
    }

    public function getSellingUnit(): ?UnitOfMeasure
    {
        foreach ($this->unitsOfMeasure as $uom) {
            if ($uom->isSelling()) return $uom;
        }
        return null;
    }

    public function getInventoryUnit(): ?UnitOfMeasure
    {
        foreach ($this->unitsOfMeasure as $uom) {
            if ($uom->isInventory()) return $uom;
        }
        return null;
    }

    // ── Mutating methods ─────────────────────────────────────────────────────

    /**
     * Update product details.
     * null $unitsOfMeasure = preserve existing (confirmed PR #37 pattern)
     * []   $unitsOfMeasure = explicitly clear all UoMs
     */
    public function updateDetails(
        string  $name,
        float   $price,
        string  $currency    = 'USD',
        ?string $description = null,
        ?string $category    = null,
        ?string $type        = null,
        ?array  $unitsOfMeasure = null,  // null = preserve, [] = clear
        ?array  $attributes  = null,
        ?array  $metadata    = null,
    ): void {
        $this->name        = $name;
        $this->price       = new Money($price, $currency);
        $this->description = $description;
        $this->category    = $category;
        $this->attributes  = $attributes;
        $this->metadata    = $metadata;

        if ($type !== null) {
            $this->type = new ProductType($type);
        }
        if ($unitsOfMeasure !== null) {
            $this->unitsOfMeasure = array_map(
                fn ($u) => $u instanceof UnitOfMeasure ? $u : UnitOfMeasure::fromArray($u),
                $unitsOfMeasure
            );
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeStatus(string $status): void
    {
        $this->status    = new ProductStatus($status);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function configureTracking(
        bool $batches,
        bool $lots,
        bool $serials,
        bool $expiry,
    ): void {
        $this->trackBatches  = $batches;
        $this->trackLots     = $lots;
        $this->trackSerials  = $serials;
        $this->trackExpiry   = $expiry;
        $this->updatedAt     = new DateTimeImmutable();
    }

    public function updateReorderConfig(
        ?float $reorderPoint,
        ?float $safetyStock,
        ?int   $leadTimeDays,
    ): void {
        $this->reorderPoint = $reorderPoint;
        $this->safetyStock  = $safetyStock;
        $this->leadTimeDays = $leadTimeDays;
        $this->updatedAt    = new DateTimeImmutable();
    }

    public function assignId(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('Product ID already assigned');
        }
        $this->id = $id;
    }

    // ── Convenience checks ───────────────────────────────────────────────────
    public function isStockable(): bool   { return $this->type->isStockable(); }
    public function requiresBatchTracking(): bool { return $this->trackBatches; }
    public function requiresLotTracking(): bool   { return $this->trackLots; }
    public function requiresSerialTracking(): bool { return $this->trackSerials; }
    public function requiresExpiryTracking(): bool { return $this->trackExpiry; }
    public function isActive(): bool      { return $this->status->isActive(); }
    public function isSellable(): bool    { return $this->status->isSellable(); }
    public function hasUomForType(string $type): bool
    {
        return collect($this->unitsOfMeasure)->contains(fn ($u) => $u->type() === $type);
    }
}
