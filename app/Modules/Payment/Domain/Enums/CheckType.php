<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum CheckType: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
