<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum GrnStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
}
