<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum TransactionType: string
{
    case SALE = 'sale';
    case PURCHASE = 'purchase';
    case RETURN = 'return';
    case STOCK_TRANSFER = 'stock_transfer';
    case EXPENSE = 'expense';
    case OPENING_STOCK = 'opening_stock';
    case SELL_RETURN = 'sell_return';
    case PURCHASE_RETURN = 'purchase_return';
    case DRAFT = 'draft';
    case QUOTATION = 'quotation';
}
