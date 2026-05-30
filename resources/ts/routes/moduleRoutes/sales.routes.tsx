import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/sales/pages/SalesDashboardPage'), 'SalesDashboardPage');
const quotations = () => import('../../modules/sales/pages/SalesQuotationPages');
const orders = () => import('../../modules/sales/pages/SalesOrderPages');
const deliveries = () => import('../../modules/sales/pages/SalesGdnPages');
const invoices = () => import('../../modules/sales/pages/SalesInvoicePages');
const payments = () => import('../../modules/sales/pages/SalesPaymentPages');
const returns = () => import('../../modules/sales/pages/SalesReturnPages');
const settings = () => lazyNamed(() => import('../../modules/sales/pages/SalesSettingsPage'), 'SalesSettingsPage');

export const salesRoutes: RouteObject[] = [
    { element: dashboard(), path: 'sales' },
    { element: lazyNamed(quotations, 'SalesQuotationListPage'), path: 'sales/quotations' },
    { element: lazyNamed(quotations, 'SalesQuotationCreatePage'), path: 'sales/quotations/new' },
    { element: lazyNamed(quotations, 'SalesQuotationDetailPage'), path: 'sales/quotations/:id' },
    { element: lazyNamed(orders, 'SalesOrderListPage'), path: 'sales/orders' },
    { element: lazyNamed(orders, 'SalesOrderCreatePage'), path: 'sales/orders/new' },
    { element: lazyNamed(orders, 'SalesOrderDetailPage'), path: 'sales/orders/:id' },
    { element: lazyNamed(orders, 'SalesOrderEditPage'), path: 'sales/orders/:id/edit' },
    { element: lazyNamed(deliveries, 'GdnListPage'), path: 'sales/deliveries' },
    { element: lazyNamed(deliveries, 'GdnCreatePage'), path: 'sales/deliveries/new' },
    { element: lazyNamed(deliveries, 'GdnDetailPage'), path: 'sales/deliveries/:id' },
    { element: lazyNamed(deliveries, 'GdnEditPage'), path: 'sales/deliveries/:id/edit' },
    { element: lazyNamed(invoices, 'SalesInvoiceListPage'), path: 'sales/invoices' },
    { element: lazyNamed(invoices, 'SalesInvoiceCreatePage'), path: 'sales/invoices/new' },
    { element: lazyNamed(invoices, 'SalesInvoiceDetailPage'), path: 'sales/invoices/:id' },
    { element: lazyNamed(invoices, 'SalesInvoiceEditPage'), path: 'sales/invoices/:id/edit' },
    { element: lazyNamed(payments, 'SalesPaymentListPage'), path: 'sales/payments' },
    { element: lazyNamed(payments, 'SalesPaymentCreatePage'), path: 'sales/payments/new' },
    { element: lazyNamed(payments, 'SalesPaymentDetailPage'), path: 'sales/payments/:id' },
    { element: lazyNamed(payments, 'CustomerAdvanceListPage'), path: 'sales/advances' },
    { element: lazyNamed(returns, 'SalesReturnListPage'), path: 'sales/returns' },
    { element: lazyNamed(returns, 'SalesReturnCreatePage'), path: 'sales/returns/new' },
    { element: lazyNamed(returns, 'SalesReturnDetailPage'), path: 'sales/returns/:id' },
    { element: lazyNamed(payments, 'CustomerRefundListPage'), path: 'sales/refunds' },
    { element: settings(), path: 'sales/settings' },
];
