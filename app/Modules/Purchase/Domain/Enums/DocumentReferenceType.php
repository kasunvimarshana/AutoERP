<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum DocumentReferenceType: string
{
    case PurchaseOrder = 'PO';
    case GoodsReceipt = 'GRN';
    case Invoice = 'INVOICE';
    case PurchaseReturn = 'PURCHASE_RETURN';
}
