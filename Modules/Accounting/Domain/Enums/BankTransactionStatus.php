<?php

namespace Modules\Accounting\Domain\Enums;

enum BankTransactionStatus: string
{
    case Unreconciled = 'unreconciled';
    case Reconciled   = 'reconciled';
}
