<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
