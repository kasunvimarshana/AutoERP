<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case CHEQUE = 'cheque';
    case BANK_TRANSFER = 'bank_transfer';
    case MOBILE_MONEY = 'mobile_money';
    case CREDIT = 'credit';
    case OTHER = 'other';
}
