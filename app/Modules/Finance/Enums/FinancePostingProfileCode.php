<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum FinancePostingProfileCode: string
{
    case SalesInvoice = 'sales_invoice';
    case PurchaseInvoice = 'purchase_invoice';
    case VehicleServiceInvoice = 'vehicle_service_invoice';
    case CustomerReceipt = 'customer_receipt';
    case SupplierPayment = 'supplier_payment';
    case CustomerAdvance = 'customer_advance';
    case SupplierAdvance = 'supplier_advance';
    case InventoryReceipt = 'inventory_receipt';
    case InventoryIssue = 'inventory_issue';
    case SalesReturn = 'sales_return';
    case PurchaseReturn = 'purchase_return';
}