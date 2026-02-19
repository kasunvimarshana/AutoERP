<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

/**
 * Customer Type Enum
 *
 * Defines possible types for customers
 */
enum CustomerType: string
{
    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';

    /**
     * Get all type values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if type is business
     */
    public function isBusiness(): bool
    {
        return $this === self::BUSINESS;
    }

    /**
     * Get type label
     */
    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::BUSINESS => 'Business',
        };
    }
}
