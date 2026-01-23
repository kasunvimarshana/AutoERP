<?php

declare(strict_types=1);

namespace App\Core\Enums;

/**
 * Permission Type Enum
 * 
 * Defines types of permissions in the system
 */
enum PermissionType: string
{
    case CREATE = 'create';
    case READ = 'read';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case MANAGE = 'manage';
    case EXPORT = 'export';
    case IMPORT = 'import';

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
     * Get enum label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::READ => 'Read',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
            self::MANAGE => 'Manage',
            self::EXPORT => 'Export',
            self::IMPORT => 'Import',
        };
    }

    /**
     * Get permission name for a resource
     *
     * @param string $resource
     * @return string
     */
    public function forResource(string $resource): string
    {
        return "{$resource}.{$this->value}";
    }
}
