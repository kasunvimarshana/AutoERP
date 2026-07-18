<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum FinanceAccountRoleCode: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Receivable = 'receivable';
    case Payable = 'payable';
    case GoodsReceivedNotInvoiced = 'goods_received_not_invoiced';
    case Revenue = 'revenue';
    case ServiceRevenue = 'service_revenue';
    case Expense = 'expense';
    case Inventory = 'inventory';
    case CostOfGoodsSold = 'cost_of_goods_sold';
    case TaxPayable = 'tax_payable';
    case TaxReceivable = 'tax_receivable';
    case WithholdingReceivable = 'withholding_receivable';
    case WithholdingPayable = 'withholding_payable';
    case CustomerAdvance = 'customer_advance';
    case SupplierAdvance = 'supplier_advance';
    case SalesRevenue = 'sales_revenue';
    case PurchaseExpense = 'purchase_expense';
}