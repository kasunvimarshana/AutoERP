import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const purchasePage = () => lazyNamed(() => import('../../modules/purchase/pages/PurchasePage'), 'PurchasePage');

export const purchaseRoutes: RouteObject[] = [
    { element: purchasePage(), path: 'purchase' },
    { element: purchasePage(), path: 'purchase/orders' },
    { element: purchasePage(), path: 'purchase/orders/new' },
    { element: purchasePage(), path: 'purchase/orders/create' },
    { element: purchasePage(), path: 'purchase/grn' },
    { element: purchasePage(), path: 'purchase/grns' },
    { element: purchasePage(), path: 'purchase/invoices' },
    { element: purchasePage(), path: 'purchase/payments' },
    { element: purchasePage(), path: 'purchase/advances' },
    { element: purchasePage(), path: 'purchase/returns' },
    { element: purchasePage(), path: 'purchase/refunds' },
];
