import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const customerListPage = () => lazyNamed(() => import('../../modules/customer/pages/CustomerListPage'), 'CustomerListPage');
const customerCreatePage = () => lazyNamed(() => import('../../modules/customer/pages/CustomerCreatePage'), 'CustomerCreatePage');
const customerDetailPage = () => lazyNamed(() => import('../../modules/customer/pages/CustomerDetailPage'), 'CustomerDetailPage');
const customerEditPage = () => lazyNamed(() => import('../../modules/customer/pages/CustomerEditPage'), 'CustomerEditPage');

export const customerRoutes: RouteObject[] = [
    { element: customerListPage(), path: 'customers' },
    { element: customerCreatePage(), path: 'customers/new' },
    { element: customerDetailPage(), path: 'customers/:id' },
    { element: customerEditPage(), path: 'customers/:id/edit' },
];
