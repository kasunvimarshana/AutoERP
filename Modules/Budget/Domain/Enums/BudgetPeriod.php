<?php

namespace Modules\Budget\Domain\Enums;

enum BudgetPeriod: string
{
    case Monthly   = 'monthly';
    case Quarterly = 'quarterly';
    case Annually  = 'annually';
}
