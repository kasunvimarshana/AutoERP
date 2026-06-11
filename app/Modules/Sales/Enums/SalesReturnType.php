<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesReturnType: string
{
    case ReferencedCustomerReturn = 'referenced_customer_return';
    case ManualCustomerReturn = 'manual_customer_return';
    case CreditNoteOnly = 'credit_note_only';
    case InventoryAdjustmentOnly = 'inventory_adjustment_only';
    case WarrantyReplacement = 'warranty_replacement';
    case ExchangeReturn = 'exchange_return';
    case OpeningImportedReturn = 'opening_imported_return';

    public function affectsInventory(): bool
    {
        return ! in_array($this, [self::CreditNoteOnly], true);
    }

    public function affectsCustomerBalance(): bool
    {
        return ! in_array($this, [self::InventoryAdjustmentOnly, self::WarrantyReplacement], true);
    }
}
