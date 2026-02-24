<?php

namespace Modules\Accounting\Domain\Enums;

enum BankTransactionType: string
{
    case Credit = 'credit';
    case Debit  = 'debit';
}
