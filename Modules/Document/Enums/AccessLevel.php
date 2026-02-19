<?php

declare(strict_types=1);

namespace Modules\Document\Enums;

enum AccessLevel: string
{
    case PRIVATE = 'private';
    case SHARED = 'shared';
    case PUBLIC = 'public';

    public function label(): string
    {
        return match ($this) {
            self::PRIVATE => 'Private',
            self::SHARED => 'Shared',
            self::PUBLIC => 'Public',
        };
    }
}
