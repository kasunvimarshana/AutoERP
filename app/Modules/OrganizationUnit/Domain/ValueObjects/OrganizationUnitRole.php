<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\ValueObjects;

enum OrganizationUnitRole: string
{
    case MANAGER = 'manager';
    case MEMBER = 'member';
    case VIEWER = 'viewer';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Manager',
            self::MEMBER => 'Member',
            self::VIEWER => 'Viewer',
            self::ADMIN => 'Administrator',
        };
    }

    public function isManagementRole(): bool
    {
        return in_array($this, [self::MANAGER, self::ADMIN], true);
    }
}
