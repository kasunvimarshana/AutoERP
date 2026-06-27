<?php

declare(strict_types=1);

namespace Modules\PrivateObject\Enums;

enum PrivateObjectScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Rejected = 'rejected';
}
