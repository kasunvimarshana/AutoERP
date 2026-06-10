<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum ChequePrintStatus: string
{
    case Previewed = 'previewed';
    case Printed = 'printed';
    case Cancelled = 'cancelled';
}
