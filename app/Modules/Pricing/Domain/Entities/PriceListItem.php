<?php
declare(strict_types=1);
namespace Modules\Pricing\Domain\Entities;

/**
 * A product-specific price override within a price list.
 *
 * Allows per-product or per-variant pricing rules to be defined within a
 * named price list, supporting fixed prices, percentage discounts, and
 * minimum quantity thresholds (tiered pricing).
 */
class PriceListItem
{
    public const TYPE_FIXED      = 'fixed';       // absolute unit price
    public const TYPE_PERCENTAGE = 'percentage';  // % off base price

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $priceListId,
        private int $productId,
        private ?int $variantId,           // null = applies to all variants
        private string $priceType,         // fixed | percentage
        private float $value,              // price OR discount %
        private float $minQuantity,        // minimum qty for this tier
        private string $currency,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getPriceListId(): int { return $this->priceListId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getPriceType(): string { return $this->priceType; }
    public function getValue(): float { return $this->value; }
    public function getMinQuantity(): float { return $this->minQuantity; }
    public function getCurrency(): string { return $this->currency; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    /**
     * Calculate the effective price for a given base price and quantity.
     */
    public function computePrice(float $basePrice, float $quantity): float
    {
        if ($quantity < $this->minQuantity) {
            return $basePrice;  // tier not applicable — return base
        }
        return match ($this->priceType) {
            self::TYPE_FIXED      => $this->value,
            self::TYPE_PERCENTAGE => $basePrice * (1 - $this->value / 100),
            default               => $basePrice,
        };
    }
}
