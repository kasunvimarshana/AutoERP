<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseReturnType: string
{
    case Referenced = 'referenced';
    case ManualSupplierReturn = 'manual_supplier_return';
}
