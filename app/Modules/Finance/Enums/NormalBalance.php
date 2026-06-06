<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum NormalBalance: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
