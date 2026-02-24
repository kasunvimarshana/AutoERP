<?php

namespace Modules\Budget\Domain\Enums;

enum BudgetStatus: string
{
    case Draft    = 'draft';
    case Approved = 'approved';
    case Closed   = 'closed';
}
