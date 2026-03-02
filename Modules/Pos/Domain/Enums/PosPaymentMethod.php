<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Enums;

enum PosPaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Mixed = 'mixed';
    case Digital = 'digital';
}
