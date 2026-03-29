<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;

class UnitOfMeasure
{
    public const TYPE_BUYING    = 'buying';
    public const TYPE_SELLING   = 'selling';
    public const TYPE_INVENTORY = 'inventory';

    public const VALID_TYPES = [
        self::TYPE_BUYING,
        self::TYPE_SELLING,
        self::TYPE_INVENTORY,
    ];

    private string $unit;

    private string $type;

    private float $conversionFactor;

    public function __construct(string $unit, string $type, float $conversionFactor = 1.0)
    {
        if (! in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid UoM type "%s". Valid types: %s', $type, implode(', ', self::VALID_TYPES))
            );
        }
        if ($conversionFactor <= 0) {
            throw new InvalidArgumentException('Conversion factor must be greater than zero.');
        }
        $this->unit             = $unit;
        $this->type             = $type;
        $this->conversionFactor = $conversionFactor;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getConversionFactor(): float
    {
        return $this->conversionFactor;
    }

    public function isBuying(): bool
    {
        return $this->type === self::TYPE_BUYING;
    }

    public function isSelling(): bool
    {
        return $this->type === self::TYPE_SELLING;
    }

    public function isInventory(): bool
    {
        return $this->type === self::TYPE_INVENTORY;
    }

    public function equals(self $other): bool
    {
        return $this->unit === $other->unit
            && $this->type === $other->type
            && $this->conversionFactor === $other->conversionFactor;
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
            $data['unit'],
            $data['type'],
            (float) ($data['conversion_factor'] ?? 1.0),
        );
    }

    public function __toString(): string
    {
        return $this->unit;
    }
}
