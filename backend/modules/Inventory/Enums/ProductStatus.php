<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Product Status Enum
 */
enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DISCONTINUED = 'discontinued';
    case DRAFT = 'draft';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DISCONTINUED => 'Discontinued',
            self::DRAFT => 'Draft',
        };
    }
}
