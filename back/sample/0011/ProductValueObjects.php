<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;

// ═══════════════════════════════════════════════════════════════════
// ProductType — confirmed from KVAutoERP PR #37 (exact implementation)
// ═══════════════════════════════════════════════════════════════════
final class ProductType
{
    public const PHYSICAL      = 'physical';
    public const SERVICE       = 'service';
    public const DIGITAL       = 'digital';
    public const SUBSCRIPTION  = 'subscription';
    public const COMBO         = 'combo';
    public const VARIABLE      = 'variable';
    public const RAW_MATERIAL  = 'raw_material';
    public const FINISHED_GOOD = 'finished_good';
    public const WIP           = 'wip';
    public const KIT           = 'kit';

    public const VALID_TYPES = [
        self::PHYSICAL, self::SERVICE, self::DIGITAL, self::SUBSCRIPTION,
        self::COMBO, self::VARIABLE, self::RAW_MATERIAL, self::FINISHED_GOOD,
        self::WIP, self::KIT,
    ];

    // Stockable types — these have physical inventory
    public const STOCKABLE_TYPES = [
        self::PHYSICAL, self::COMBO, self::VARIABLE, self::RAW_MATERIAL,
        self::FINISHED_GOOD, self::WIP, self::KIT,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid product type "%s". Valid: %s', $value, implode(', ', self::VALID_TYPES))
            );
        }
        $this->value = $value;
    }

    public function value(): string      { return $this->value; }
    public function isPhysical(): bool   { return $this->value === self::PHYSICAL; }
    public function isService(): bool    { return $this->value === self::SERVICE; }
    public function isDigital(): bool    { return $this->value === self::DIGITAL; }
    public function isSubscription(): bool { return $this->value === self::SUBSCRIPTION; }
    public function isCombo(): bool      { return $this->value === self::COMBO; }
    public function isVariable(): bool   { return $this->value === self::VARIABLE; }
    public function isRawMaterial(): bool { return $this->value === self::RAW_MATERIAL; }
    public function isFinishedGood(): bool { return $this->value === self::FINISHED_GOOD; }
    public function isWip(): bool        { return $this->value === self::WIP; }
    public function isKit(): bool        { return $this->value === self::KIT; }
    public function isStockable(): bool  { return in_array($this->value, self::STOCKABLE_TYPES, true); }
    public function isComposite(): bool  { return in_array($this->value, [self::COMBO, self::KIT], true); }

    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string       { return $this->value; }
}


// ═══════════════════════════════════════════════════════════════════
// UnitOfMeasure — confirmed from KVAutoERP PR #37
// buying|selling|inventory with conversion_factor
// ═══════════════════════════════════════════════════════════════════
final class UnitOfMeasure
{
    public const TYPE_BUYING    = 'buying';
    public const TYPE_SELLING   = 'selling';
    public const TYPE_INVENTORY = 'inventory';
    public const TYPE_PRODUCTION = 'production';
    public const TYPE_SHIPPING  = 'shipping';

    public const VALID_TYPES = [
        self::TYPE_BUYING, self::TYPE_SELLING, self::TYPE_INVENTORY,
        self::TYPE_PRODUCTION, self::TYPE_SHIPPING,
    ];

    public function __construct(
        private readonly string $unit,
        private readonly string $type,
        private readonly float  $conversionFactor = 1.0,
    ) {
        if (trim($unit) === '') {
            throw new InvalidArgumentException('UnitOfMeasure unit cannot be empty');
        }
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid UoM type "%s". Valid: %s', $type, implode(', ', self::VALID_TYPES))
            );
        }
        if ($conversionFactor <= 0) {
            throw new InvalidArgumentException("Conversion factor must be > 0: {$conversionFactor}");
        }
    }

    public function unit(): string             { return $this->unit; }
    public function type(): string             { return $this->type; }
    public function conversionFactor(): float  { return $this->conversionFactor; }

    public function isBuying(): bool     { return $this->type === self::TYPE_BUYING; }
    public function isSelling(): bool    { return $this->type === self::TYPE_SELLING; }
    public function isInventory(): bool  { return $this->type === self::TYPE_INVENTORY; }
    public function isProduction(): bool { return $this->type === self::TYPE_PRODUCTION; }
    public function isShipping(): bool   { return $this->type === self::TYPE_SHIPPING; }

    /** Convert from this UoM to inventory base quantity */
    public function toBaseQuantity(float $quantity): float
    {
        return round($quantity * $this->conversionFactor, 6);
    }

    /** Convert from inventory base quantity to this UoM */
    public function fromBaseQuantity(float $baseQuantity): float
    {
        return round($baseQuantity / $this->conversionFactor, 6);
    }

    public function toArray(): array
    {
        return [
            'unit'              => $this->unit,
            'type'              => $this->type,
            'conversion_factor' => $this->conversionFactor,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            unit:             $data['unit'],
            type:             $data['type'],
            conversionFactor: (float) ($data['conversion_factor'] ?? 1.0),
        );
    }

    public function equals(self $other): bool
    {
        return $this->unit === $other->unit
            && $this->type === $other->type
            && $this->conversionFactor === $other->conversionFactor;
    }

    public function __toString(): string { return "{$this->unit} ({$this->type})"; }
}


// ═══════════════════════════════════════════════════════════════════
// ProductStatus — status lifecycle value object
// ═══════════════════════════════════════════════════════════════════
final class ProductStatus
{
    public const DRAFT        = 'draft';
    public const ACTIVE       = 'active';
    public const INACTIVE     = 'inactive';
    public const ARCHIVED     = 'archived';
    public const DISCONTINUED = 'discontinued';

    public const VALID = [
        self::DRAFT, self::ACTIVE, self::INACTIVE, self::ARCHIVED, self::DISCONTINUED,
    ];

    public const SELLABLE = [self::ACTIVE];
    public const PURCHASABLE = [self::ACTIVE, self::INACTIVE];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid product status: {$value}");
        }
    }

    public function value(): string       { return $this->value; }
    public function isDraft(): bool       { return $this->value === self::DRAFT; }
    public function isActive(): bool      { return $this->value === self::ACTIVE; }
    public function isSellable(): bool    { return in_array($this->value, self::SELLABLE, true); }
    public function isPurchasable(): bool { return in_array($this->value, self::PURCHASABLE, true); }
    public function equals(self $o): bool { return $this->value === $o->value; }
    public function __toString(): string  { return $this->value; }
}
