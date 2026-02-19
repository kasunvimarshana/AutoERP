<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

/**
 * Account Status Enum
 */
enum AccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
