<?php

declare(strict_types=1);

namespace Modules\Item\Domain\Enums;

enum ItemStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Discontinued = 'DISCONTINUED';
}
