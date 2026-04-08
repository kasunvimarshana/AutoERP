<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

// ═══════════════════════════════════════════════════════════════════
// ValuationMethod — user-selectable at runtime, stored in settings
// ═══════════════════════════════════════════════════════════════════
final class ValuationMethod
{
    public const FIFO          = 'FIFO';
    public const LIFO          = 'LIFO';
    public const AVCO          = 'AVCO';   // Weighted Average Cost
    public const FEFO          = 'FEFO';   // First Expired First Out
    public const FMFO          = 'FMFO';   // First Manufactured First Out
    public const SPECIFIC_ID   = 'specific_id';
    public const STANDARD_COST = 'standard_cost';
    public const RETAIL        = 'retail';

    public const VALID = [
        self::FIFO, self::LIFO, self::AVCO, self::FEFO,
        self::FMFO, self::SPECIFIC_ID, self::STANDARD_COST, self::RETAIL,
    ];

    // Methods that use costing layers (stacks consumed on issue)
    public const LAYER_BASED = [self::FIFO, self::LIFO, self::FEFO, self::FMFO, self::SPECIFIC_ID];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid valuation method "%s". Valid: %s', $value, implode(', ', self::VALID))
            );
        }
    }

    public function value(): string      { return $this->value; }
    public function isFifo(): bool       { return $this->value === self::FIFO; }
    public function isLifo(): bool       { return $this->value === self::LIFO; }
    public function isAvco(): bool       { return $this->value === self::AVCO; }
    public function isFefo(): bool       { return $this->value === self::FEFO; }
    public function isFmfo(): bool       { return $this->value === self::FMFO; }
    public function isSpecificId(): bool { return $this->value === self::SPECIFIC_ID; }
    public function isStandardCost(): bool { return $this->value === self::STANDARD_COST; }
    public function isRetail(): bool     { return $this->value === self::RETAIL; }
    public function isLayerBased(): bool { return in_array($this->value, self::LAYER_BASED, true); }
    public function __toString(): string { return $this->value; }
}


// ═══════════════════════════════════════════════════════════════════
// StockRotationStrategy — physical picking order
// ═══════════════════════════════════════════════════════════════════
final class StockRotationStrategy
{
    public const FIFO = 'FIFO';  // First In First Out — oldest received first
    public const LIFO = 'LIFO';  // Last In First Out
    public const FEFO = 'FEFO';  // First Expired First Out — nearest expiry first
    public const FMFO = 'FMFO';  // First Manufactured First Out
    public const LEFO = 'LEFO';  // Least Expiry First Out (shortest remaining life)

    public const VALID = [self::FIFO, self::LIFO, self::FEFO, self::FMFO, self::LEFO];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid rotation strategy: {$value}");
        }
    }

    public function value(): string { return $this->value; }
    public function isFifo(): bool  { return $this->value === self::FIFO; }
    public function isLifo(): bool  { return $this->value === self::LIFO; }
    public function isFefo(): bool  { return $this->value === self::FEFO; }
    public function isFmfo(): bool  { return $this->value === self::FMFO; }
    public function isLefo(): bool  { return $this->value === self::LEFO; }
    public function __toString(): string { return $this->value; }
}


// ═══════════════════════════════════════════════════════════════════
// AllocationAlgorithm — how stock is reserved for orders
// ═══════════════════════════════════════════════════════════════════
final class AllocationAlgorithm
{
    public const STRICT_RESERVATION  = 'strict_reservation';
    public const SOFT_RESERVATION    = 'soft_reservation';
    public const FAIR_SHARE          = 'fair_share';
    public const PRIORITY_BASED      = 'priority_based';
    public const WAVE_PICKING        = 'wave_picking';
    public const ZONE_PICKING        = 'zone_picking';
    public const BATCH_PICKING       = 'batch_picking';
    public const CLUSTER_PICKING     = 'cluster_picking';

    public const VALID = [
        self::STRICT_RESERVATION, self::SOFT_RESERVATION,
        self::FAIR_SHARE, self::PRIORITY_BASED,
        self::WAVE_PICKING, self::ZONE_PICKING,
        self::BATCH_PICKING, self::CLUSTER_PICKING,
    ];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException("Invalid allocation algorithm: {$value}");
        }
    }

    public function value(): string            { return $this->value; }
    public function isStrictReservation(): bool { return $this->value === self::STRICT_RESERVATION; }
    public function isSoftReservation(): bool  { return $this->value === self::SOFT_RESERVATION; }
    public function isFairShare(): bool        { return $this->value === self::FAIR_SHARE; }
    public function isPriorityBased(): bool    { return $this->value === self::PRIORITY_BASED; }
    public function isWavePicking(): bool      { return $this->value === self::WAVE_PICKING; }
    public function isZonePicking(): bool      { return $this->value === self::ZONE_PICKING; }
    public function isBatchPicking(): bool     { return $this->value === self::BATCH_PICKING; }
    public function isClusterPicking(): bool   { return $this->value === self::CLUSTER_PICKING; }
    public function isPickingAlgorithm(): bool {
        return in_array($this->value, [
            self::WAVE_PICKING, self::ZONE_PICKING,
            self::BATCH_PICKING, self::CLUSTER_PICKING,
        ], true);
    }
    public function __toString(): string { return $this->value; }
}


// ═══════════════════════════════════════════════════════════════════
// MovementType — all ledger movement types
// ═══════════════════════════════════════════════════════════════════
final class MovementType
{
    // Inbound
    public const PURCHASE_RECEIPT      = 'purchase_receipt';
    public const PRODUCTION_PRODUCE    = 'production_produce';
    public const TRANSFER_IN           = 'transfer_in';
    public const ADJUSTMENT_POSITIVE   = 'adjustment_positive';
    public const SALES_RETURN          = 'sales_return';
    public const CONSIGNMENT_IN        = 'consignment_in';
    public const OPENING_BALANCE       = 'opening_balance';
    public const QUARANTINE_RELEASE    = 'quarantine_release';

    // Outbound
    public const SALES_ISSUE           = 'sales_issue';
    public const PRODUCTION_CONSUME    = 'production_consume';
    public const TRANSFER_OUT          = 'transfer_out';
    public const ADJUSTMENT_NEGATIVE   = 'adjustment_negative';
    public const PURCHASE_RETURN       = 'purchase_return';
    public const SCRAP                 = 'scrap';
    public const WRITE_OFF             = 'write_off';
    public const EXPIRED_REMOVAL       = 'expired_removal';
    public const DAMAGE                = 'damage';
    public const CONSIGNMENT_OUT       = 'consignment_out';
    public const THEFT                 = 'theft';
    public const SAMPLE                = 'sample';
    public const DONATION              = 'donation';

    // Neutral / adjustment
    public const PHYSICAL_COUNT_ADJ    = 'physical_count_adjustment';
    public const CYCLE_COUNT_ADJ       = 'cycle_count_adjustment';
    public const QUARANTINE            = 'quarantine';
    public const REWORK                = 'rework';
    public const REVALUATION           = 'revaluation';
    public const RETURN_TO_VENDOR      = 'return_to_vendor';

    public const INBOUND_TYPES = [
        self::PURCHASE_RECEIPT, self::PRODUCTION_PRODUCE, self::TRANSFER_IN,
        self::ADJUSTMENT_POSITIVE, self::SALES_RETURN, self::CONSIGNMENT_IN,
        self::OPENING_BALANCE, self::QUARANTINE_RELEASE,
    ];

    public const OUTBOUND_TYPES = [
        self::SALES_ISSUE, self::PRODUCTION_CONSUME, self::TRANSFER_OUT,
        self::ADJUSTMENT_NEGATIVE, self::PURCHASE_RETURN, self::SCRAP,
        self::WRITE_OFF, self::EXPIRED_REMOVAL, self::DAMAGE,
        self::CONSIGNMENT_OUT, self::THEFT, self::SAMPLE, self::DONATION,
    ];

    public static function direction(string $movementType): string
    {
        return in_array($movementType, self::INBOUND_TYPES, true) ? 'IN' : 'OUT';
    }
}
