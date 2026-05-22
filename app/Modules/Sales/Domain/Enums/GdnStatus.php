<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Enums;

enum GdnStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
}
