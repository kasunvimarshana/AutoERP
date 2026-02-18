<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

/**
 * Product Type Enum
 *
 * Defines different types of products in the system.
 * Supports product, service, and combo offerings.
 *
 * @package Modules\Core\Enums
 */
enum ProductTypeEnum: string
{
    case PRODUCT = 'product';
    case SERVICE = 'service';
    case COMBO = 'combo';
    case DIGITAL = 'digital';
    case SUBSCRIPTION = 'subscription';

    /**
     * Get all product type values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => 'Physical Product',
            self::SERVICE => 'Service',
            self::COMBO => 'Product/Service Combo',
            self::DIGITAL => 'Digital Product',
            self::SUBSCRIPTION => 'Subscription',
        };
    }

    /**
     * Check if inventory tracking is required
     *
     * @return bool
     */
    public function requiresInventory(): bool
    {
        return in_array($this, [
            self::PRODUCT,
            self::COMBO,
            self::DIGITAL,
        ], true);
    }

    /**
     * Check if this type supports variable units
     *
     * @return bool
     */
    public function supportsVariableUnits(): bool
    {
        return in_array($this, [
            self::PRODUCT,
            self::SERVICE,
            self::COMBO,
        ], true);
    }

    /**
     * Check if buying and selling units can differ
     *
     * @return bool
     */
    public function allowsDifferentBuyingSelling(): bool
    {
        return in_array($this, [
            self::PRODUCT,
            self::COMBO,
        ], true);
    }

    /**
     * Check if this type supports bundling
     *
     * @return bool
     */
    public function supportsBundle(): bool
    {
        return $this === self::COMBO;
    }
}
