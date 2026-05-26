export type DashboardKpi = {
    id: string;
    label: string;
    value: string;
    detail: string;
    delta: string;
    tone: 'neutral' | 'positive' | 'warning';
};

export type DashboardActivity = {
    id: string;
    actor: string;
    action: string;
    target: string;
    timestamp: string;
    tenant: string;
};

export type DashboardLowStockItem = {
    id: string;
    product: string;
    sku: string;
    warehouse: string;
    availableQty: number;
    reorderPoint: number;
};

export type DashboardApproval = {
    id: string;
    title: string;
    owner: string;
    amount: string;
    dueLabel: string;
};

export type DashboardQuickAction = {
    id: string;
    label: string;
    description: string;
    path: string;
};

export type DashboardOverview = {
    kpis: DashboardKpi[];
    auditActivity: DashboardActivity[];
    lowStockItems: DashboardLowStockItem[];
    pendingApprovals: DashboardApproval[];
    quickActions: DashboardQuickAction[];
};
