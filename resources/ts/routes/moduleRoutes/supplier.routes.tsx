import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const supplierListPage = () => lazyNamed(() => import('../../modules/supplier/pages/SupplierListPage'), 'SupplierListPage');
const supplierCreatePage = () => lazyNamed(() => import('../../modules/supplier/pages/SupplierCreatePage'), 'SupplierCreatePage');
const supplierDetailPage = () => lazyNamed(() => import('../../modules/supplier/pages/SupplierDetailPage'), 'SupplierDetailPage');
const supplierEditPage = () => lazyNamed(() => import('../../modules/supplier/pages/SupplierEditPage'), 'SupplierEditPage');

export const supplierRoutes: RouteObject[] = [
    { element: supplierListPage(), path: 'suppliers' },
    { element: supplierCreatePage(), path: 'suppliers/new' },
    { element: supplierDetailPage(), path: 'suppliers/:id' },
    { element: supplierEditPage(), path: 'suppliers/:id/edit' },
];
