<?php

declare(strict_types=1);

namespace Modules\Document\Enums;

enum PermissionType: string
{
    case VIEW = 'view';
    case DOWNLOAD = 'download';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case SHARE = 'share';

    public function label(): string
    {
        return match ($this) {
            self::VIEW => 'View',
            self::DOWNLOAD => 'Download',
            self::EDIT => 'Edit',
            self::DELETE => 'Delete',
            self::SHARE => 'Share',
        };
    }

    public function includes(PermissionType $permission): bool
    {
        return match ($this) {
            self::VIEW => $permission === self::VIEW,
            self::DOWNLOAD => in_array($permission, [self::VIEW, self::DOWNLOAD]),
            self::EDIT => in_array($permission, [self::VIEW, self::DOWNLOAD, self::EDIT]),
            self::DELETE => in_array($permission, [self::VIEW, self::DOWNLOAD, self::EDIT, self::DELETE]),
            self::SHARE => true,
        };
    }
}
