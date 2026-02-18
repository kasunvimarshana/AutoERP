<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum AccountingMethod: string
{
    case FIFO = 'fifo';
    case LIFO = 'lifo';
    case AVERAGE = 'average';
}
