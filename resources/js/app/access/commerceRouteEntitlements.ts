import { purchasePermissions } from '@/modules/purchase/purchasePermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const commerceRouteEntitlements: readonly EntitlementRule[] = [
    operational('/purchase/fast-purchase', ['purchase'], [
        purchasePermissions.fastPurchasesView,
        purchasePermissions.fastPurchasesExecute,
    ]),
    operational('/purchase/orders/create', ['purchase'], [purchasePermissions.ordersCreate]),
    operational('/purchase/orders/:id/edit', ['purchase'], [purchasePermissions.ordersUpdate]),
    operational('/purchase/orders/:id', ['purchase'], [purchasePermissions.ordersView]),
    operational('/purchase/orders', ['purchase'], [purchasePermissions.ordersView]),
    operational('/purchase/goods-receipts/create', ['purchase'], [purchasePermissions.goodsReceiptsCreate]),
    operational('/purchase/goods-receipts/:id', ['purchase'], [purchasePermissions.goodsReceiptsView]),
    operational('/purchase/goods-receipts', ['purchase'], [purchasePermissions.goodsReceiptsView]),
    operational('/purchase/manual-supplier-returns/create', ['purchase'], [purchasePermissions.returnsCreateManual]),
    operational('/purchase/returns/create', ['purchase'], [purchasePermissions.returnsCreate]),
    operational('/purchase/returns/:id', ['purchase'], [purchasePermissions.returnsView]),
    operational('/purchase/returns', ['purchase'], [purchasePermissions.returnsView]),
    operational('/purchase/invoices/create', ['purchase'], [purchasePermissions.supplierInvoicesCreate]),
    operational('/purchase/invoices', ['purchase'], [purchasePermissions.supplierInvoicesView]),
    operational('/purchase/payments/create', ['purchase', 'payment'], [purchasePermissions.paymentsExecute]),
    operational('/purchase/payments/prepare', ['purchase', 'payment'], [purchasePermissions.paymentsExecute]),
    operational('/purchase/payments', ['purchase', 'payment'], [purchasePermissions.paymentsView]),
    operational('/purchase/debit-notes/create', ['purchase'], [purchasePermissions.debitNotesCreate]),
    operational('/purchase/debit-notes/:id', ['purchase'], [purchasePermissions.debitNotesView]),
    operational('/purchase/debit-notes', ['purchase'], [purchasePermissions.debitNotesView]),
];
