<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Enums;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            Status::Active => 'Active',
            Status::Inactive => 'Inactive',
            Status::Archived => 'Archived',
        };
    }
}
