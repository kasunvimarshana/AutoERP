import { useQuery } from '@tanstack/react-query';
import type { DashboardOverview } from '../types';

type PreviewState = 'default' | 'loading' | 'empty' | 'error';

const defaultDashboardOverview: DashboardOverview = {
    kpis: [
        { id: 'sales-orders', label: 'Sales Orders', value: '18', detail: '5 due for dispatch today', delta: '+12%', tone: 'positive' },
        { id: 'purchase-orders', label: 'Purchase Orders', value: '9', detail: '3 awaiting goods receipt', delta: '-2%', tone: 'neutral' },
        { id: 'inventory-alerts', label: 'Inventory Alerts', value: '14', detail: '6 urgent replenishment items', delta: '6 urgent', tone: 'warning' },
        { id: 'receivables', label: 'Receivables', value: '$148.2k', detail: '12 invoices past due', delta: '+4.3%', tone: 'warning' },
        { id: 'payables', label: 'Payables', value: '$89.4k', detail: '4 supplier bills due this week', delta: '4 due', tone: 'neutral' },
        { id: 'cash-status', label: 'Cash Status', value: '$412.0k', detail: 'Across 3 connected accounts', delta: '+8.1%', tone: 'positive' },
    ],
    auditActivity: [
        {
            id: 'activity-1',
            actor: 'System Admin',
            action: 'approved',
            target: 'Purchase order PO-2026-014',
            timestamp: '2 minutes ago',
            tenant: 'Tenant 1',
        },
        {
            id: 'activity-2',
            actor: 'Warehouse Lead',
            action: 'adjusted',
            target: 'Stock level for PROD-104',
            timestamp: '19 minutes ago',
            tenant: 'Tenant 1',
        },
        {
            id: 'activity-3',
            actor: 'Finance Officer',
            action: 'posted',
            target: 'Journal entry JE-2026-041',
            timestamp: '48 minutes ago',
            tenant: 'Tenant 1',
        },
        {
            id: 'activity-4',
            actor: 'Sales Manager',
            action: 'updated',
            target: 'Customer credit limit for CUS-0021',
            timestamp: '1 hour ago',
            tenant: 'Tenant 1',
        },
    ],
    lowStockItems: [
        { id: 'stock-1', product: 'Hydraulic Filter Kit', sku: 'PROD-104', warehouse: 'Main Warehouse', availableQty: 3, reorderPoint: 12 },
        { id: 'stock-2', product: 'Control Valve Set', sku: 'PROD-233', warehouse: 'Regional Store', availableQty: 5, reorderPoint: 10 },
        { id: 'stock-3', product: 'Copper Seal Pack', sku: 'PROD-078', warehouse: 'Main Warehouse', availableQty: 7, reorderPoint: 20 },
        { id: 'stock-4', product: 'Service Lubricant 5L', sku: 'PROD-301', warehouse: 'Vehicle Bay', availableQty: 4, reorderPoint: 8 },
    ],
    pendingApprovals: [
        { id: 'approval-1', title: 'Supplier payment release', owner: 'Accounts Payable', amount: '$12,850', dueLabel: 'Needs review today' },
        { id: 'approval-2', title: 'Inventory adjustment request', owner: 'Warehouse Control', amount: '$3,480', dueLabel: 'Waiting for approver' },
        { id: 'approval-3', title: 'Customer credit override', owner: 'Sales Operations', amount: '$21,000', dueLabel: 'Escalated 30 mins ago' },
    ],
    quickActions: [
        { id: 'quick-1', label: 'Create sales order', description: 'Start a new sales order workflow.', path: '/sales/orders' },
        { id: 'quick-2', label: 'Review purchase queue', description: 'Open open purchase order workload.', path: '/purchase/orders' },
        { id: 'quick-3', label: 'Inspect low stock items', description: 'Jump into replenishment signals.', path: '/inventory/stock-levels' },
        { id: 'quick-4', label: 'Open finance reports', description: 'Review summary finance reporting.', path: '/finance/reports' },
    ],
};

const emptyDashboardOverview: DashboardOverview = {
    kpis: [
        { id: 'sales-orders', label: 'Sales Orders', value: '0', detail: 'No order activity yet', delta: '0%', tone: 'neutral' },
        { id: 'purchase-orders', label: 'Purchase Orders', value: '0', detail: 'No purchasing activity yet', delta: '0%', tone: 'neutral' },
        { id: 'inventory-alerts', label: 'Inventory Alerts', value: '0', detail: 'No alerts at the moment', delta: '0', tone: 'neutral' },
        { id: 'receivables', label: 'Receivables', value: '$0', detail: 'No outstanding customer balances', delta: '0%', tone: 'neutral' },
        { id: 'payables', label: 'Payables', value: '$0', detail: 'No supplier liabilities yet', delta: '0%', tone: 'neutral' },
        { id: 'cash-status', label: 'Cash Status', value: '$0', detail: 'No cash positions loaded', delta: '0%', tone: 'neutral' },
    ],
    auditActivity: [],
    lowStockItems: [],
    pendingApprovals: [],
    quickActions: defaultDashboardOverview.quickActions,
};

function wait(ms: number) {
    return new Promise((resolve) => {
        window.setTimeout(resolve, ms);
    });
}

export function useDashboardOverview(previewState: PreviewState = 'default') {
    return useQuery({
        queryKey: ['dashboard-overview', previewState],
        queryFn: async () => {
            await wait(previewState === 'loading' ? 1200 : 320);

            if (previewState === 'error') {
                throw new Error('Dashboard summary could not be prepared. Retry once the API wiring is ready.');
            }

            return previewState === 'empty' ? emptyDashboardOverview : defaultDashboardOverview;
        },
    });
}
