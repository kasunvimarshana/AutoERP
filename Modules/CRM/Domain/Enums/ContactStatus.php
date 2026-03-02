<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Enums;

enum ContactStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
