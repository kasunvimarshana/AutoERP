import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/inventory/pages/InventoryDashboardPage'), 'InventoryDashboardPage');
const lists = () => import('../../modules/inventory/pages/InventoryListPages');
const details = () => import('../../modules/inventory/pages/InventoryDetailPages');
const forms = () => import('../../modules/inventory/pages/InventoryFormPages');
const ops = () => import('../../modules/inventory/pages/InventoryOperationalPages');

export const inventoryRoutes: RouteObject[] = [
    { element: dashboard(), path: 'inventory' },
    { element: lazyNamed(lists, 'StockLevelListPage'), path: 'inventory/stock-levels' },
    { element: lazyNamed(lists, 'StockMovementListPage'), path: 'inventory/movements' },
    { element: lazyNamed(details, 'StockMovementDetailPage'), path: 'inventory/movements/:id' },
    { element: lazyNamed(lists, 'StockReservationListPage'), path: 'inventory/reservations' },
    { element: lazyNamed(lists, 'StockTransferListPage'), path: 'inventory/transfers' },
    { element: lazyNamed(forms, 'StockTransferCreatePage'), path: 'inventory/transfers/new' },
    { element: lazyNamed(details, 'StockTransferDetailPage'), path: 'inventory/transfers/:id' },
    { element: lazyNamed(lists, 'StockAdjustmentListPage'), path: 'inventory/adjustments' },
    { element: lazyNamed(forms, 'StockAdjustmentCreatePage'), path: 'inventory/adjustments/new' },
    { element: lazyNamed(details, 'StockAdjustmentDetailPage'), path: 'inventory/adjustments/:id' },
    { element: lazyNamed(lists, 'CycleCountListPage'), path: 'inventory/cycle-counts' },
    { element: lazyNamed(lists, 'BatchListPage'), path: 'inventory/batches' },
    { element: lazyNamed(lists, 'SerialListPage'), path: 'inventory/serials' },
    { element: lazyNamed(lists, 'ReceiptInspectionListPage'), path: 'inventory/inspections' },
    { element: lazyNamed(lists, 'PutAwayTaskListPage'), path: 'inventory/put-away' },
    { element: lazyNamed(lists, 'PickingTaskListPage'), path: 'inventory/picking' },
    { element: lazyNamed(lists, 'ValuationListPage'), path: 'inventory/valuation' },
    { element: lazyNamed(ops, 'TraceabilityPage'), path: 'inventory/traceability' },
    { element: lazyNamed(ops, 'StockAvailabilityPreviewPage'), path: 'inventory/availability-preview' },
];
