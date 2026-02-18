<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum TaxCalculationType: string
{
    case INCLUSIVE = 'inclusive';
    case EXCLUSIVE = 'exclusive';
}
