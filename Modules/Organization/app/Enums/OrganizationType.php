<?php

declare(strict_types=1);

namespace Modules\Organization\Enums;

/**
 * Organization Type Enum
 *
 * Defines the different types of organizations
 */
enum OrganizationType: string
{
    case SINGLE = 'single';
    case MULTI_BRANCH = 'multi_branch';
    case FRANCHISE = 'franchise';

    /**
     * Get all enum values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single Branch',
            self::MULTI_BRANCH => 'Multiple Branches',
            self::FRANCHISE => 'Franchise',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::SINGLE => 'Single location organization',
            self::MULTI_BRANCH => 'Organization with multiple branches',
            self::FRANCHISE => 'Franchise-based organization',
        };
    }
}
