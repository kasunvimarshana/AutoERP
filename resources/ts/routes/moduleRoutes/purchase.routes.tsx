import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/purchase/pages/PurchaseDashboardPage'), 'PurchaseDashboardPage');
const orders = () => import('../../modules/purchase/pages/PurchaseOrderPages');
const grns = () => import('../../modules/purchase/pages/PurchaseGrnPages');
const invoices = () => import('../../modules/purchase/pages/PurchaseInvoicePages');
const payments = () => import('../../modules/purchase/pages/PurchasePaymentPages');
const returns = () => import('../../modules/purchase/pages/PurchaseReturnPages');
const ledgerNotes = () => import('../../modules/purchase/pages/PurchaseLedgerNotePages');
const settings = () => lazyNamed(() => import('../../modules/purchase/pages/PurchaseSettingsPage'), 'PurchaseSettingsPage');

export const purchaseRoutes: RouteObject[] = [
    { element: dashboard(), path: 'purchase' },
    { element: lazyNamed(orders, 'PurchaseOrderListPage'), path: 'purchase/orders' },
    { element: lazyNamed(orders, 'PurchaseOrderCreatePage'), path: 'purchase/orders/new' },
    { element: lazyNamed(orders, 'PurchaseOrderCreatePage'), path: 'purchase/orders/create' },
    { element: lazyNamed(orders, 'PurchaseOrderDetailPage'), path: 'purchase/orders/:id' },
    { element: lazyNamed(orders, 'PurchaseOrderEditPage'), path: 'purchase/orders/:id/edit' },
    { element: lazyNamed(grns, 'GrnListPage'), path: 'purchase/grn' },
    { element: lazyNamed(grns, 'GrnListPage'), path: 'purchase/grns' },
    { element: lazyNamed(grns, 'GrnCreatePage'), path: 'purchase/grns/new' },
    { element: lazyNamed(grns, 'GrnDetailPage'), path: 'purchase/grns/:id' },
    { element: lazyNamed(grns, 'GrnEditPage'), path: 'purchase/grns/:id/edit' },
    { element: lazyNamed(invoices, 'PurchaseInvoiceListPage'), path: 'purchase/invoices' },
    { element: lazyNamed(invoices, 'PurchaseInvoiceCreatePage'), path: 'purchase/invoices/new' },
    { element: lazyNamed(invoices, 'PurchaseInvoiceDetailPage'), path: 'purchase/invoices/:id' },
    { element: lazyNamed(invoices, 'PurchaseInvoiceEditPage'), path: 'purchase/invoices/:id/edit' },
    { element: lazyNamed(payments, 'PurchasePaymentListPage'), path: 'purchase/payments' },
    { element: lazyNamed(payments, 'PurchasePaymentCreatePage'), path: 'purchase/payments/new' },
    { element: lazyNamed(payments, 'PurchasePaymentDetailPage'), path: 'purchase/payments/:id' },
    { element: lazyNamed(payments, 'PurchaseAdvanceListPage'), path: 'purchase/advances' },
    { element: lazyNamed(returns, 'PurchaseReturnListPage'), path: 'purchase/returns' },
    { element: lazyNamed(returns, 'PurchaseReturnCreatePage'), path: 'purchase/returns/new' },
    { element: lazyNamed(returns, 'PurchaseReturnDetailPage'), path: 'purchase/returns/:id' },
    { element: lazyNamed(returns, 'PurchaseReturnEditPage'), path: 'purchase/returns/:id/edit' },
    { element: lazyNamed(payments, 'SupplierRefundListPage'), path: 'purchase/refunds' },
    { element: lazyNamed(ledgerNotes, 'PurchaseLedgerNoteListPage'), path: 'purchase/ledger-notes' },
    { element: settings(), path: 'purchase/settings' },
];
