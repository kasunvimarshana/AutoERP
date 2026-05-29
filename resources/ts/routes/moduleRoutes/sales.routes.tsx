import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const salesPage = () => lazyNamed(() => import('../../modules/sales/pages/SalesPage'), 'SalesPage');

export const salesRoutes: RouteObject[] = [
    { element: salesPage(), path: 'sales' },
    { element: salesPage(), path: 'sales/orders' },
    { element: salesPage(), path: 'sales/orders/new' },
    { element: salesPage(), path: 'sales/deliveries' },
    { element: salesPage(), path: 'sales/invoices' },
    { element: salesPage(), path: 'sales/payments' },
    { element: salesPage(), path: 'sales/advances' },
    { element: salesPage(), path: 'sales/returns' },
    { element: salesPage(), path: 'sales/refunds' },
];
