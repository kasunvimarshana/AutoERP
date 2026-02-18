<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

/**
 * Organization Type Enum
 *
 * Defines hierarchical organization types.
 * Supports multi-level organizational structures.
 *
 * @package Modules\Core\Enums
 */
enum OrganizationTypeEnum: string
{
    case CORPORATION = 'corporation';
    case REGION = 'region';
    case COUNTRY = 'country';
    case STATE = 'state';
    case BRANCH = 'branch';
    case DEPARTMENT = 'department';
    case DIVISION = 'division';
    case TEAM = 'team';
    case FRANCHISE = 'franchise';
    case SUBSIDIARY = 'subsidiary';

    /**
     * Get all organization type values
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
            self::CORPORATION => 'Corporation',
            self::REGION => 'Region',
            self::COUNTRY => 'Country',
            self::STATE => 'State/Province',
            self::BRANCH => 'Branch',
            self::DEPARTMENT => 'Department',
            self::DIVISION => 'Division',
            self::TEAM => 'Team',
            self::FRANCHISE => 'Franchise',
            self::SUBSIDIARY => 'Subsidiary',
        };
    }

    /**
     * Get typical hierarchy level (lower is higher in hierarchy)
     *
     * @return int
     */
    public function hierarchyLevel(): int
    {
        return match ($this) {
            self::CORPORATION => 1,
            self::SUBSIDIARY => 2,
            self::REGION => 3,
            self::COUNTRY => 4,
            self::STATE => 5,
            self::DIVISION => 6,
            self::BRANCH, self::FRANCHISE => 7,
            self::DEPARTMENT => 8,
            self::TEAM => 9,
        };
    }

    /**
     * Check if this type can have children
     *
     * @return bool
     */
    public function canHaveChildren(): bool
    {
        return $this !== self::TEAM;
    }

    /**
     * Get typical parent types
     *
     * @return array<OrganizationTypeEnum>
     */
    public function typicalParents(): array
    {
        return match ($this) {
            self::CORPORATION => [],
            self::SUBSIDIARY => [self::CORPORATION],
            self::REGION => [self::CORPORATION, self::SUBSIDIARY],
            self::COUNTRY => [self::REGION],
            self::STATE => [self::COUNTRY],
            self::DIVISION => [self::CORPORATION, self::SUBSIDIARY, self::REGION],
            self::BRANCH => [self::STATE, self::COUNTRY, self::REGION],
            self::FRANCHISE => [self::STATE, self::COUNTRY, self::REGION],
            self::DEPARTMENT => [self::BRANCH, self::DIVISION],
            self::TEAM => [self::DEPARTMENT],
        };
    }
}
