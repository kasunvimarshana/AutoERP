import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const inventoryPage = () => lazyNamed(() => import('../../modules/inventory/pages/InventoryPage'), 'InventoryPage');

export const inventoryRoutes: RouteObject[] = [
    { element: inventoryPage(), path: 'inventory' },
    { element: inventoryPage(), path: 'inventory/stock-levels' },
    { element: inventoryPage(), path: 'inventory/movements' },
    { element: inventoryPage(), path: 'inventory/reservations' },
    { element: inventoryPage(), path: 'inventory/transfers' },
];
