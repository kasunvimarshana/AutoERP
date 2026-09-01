import { useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import {
    listAllocations,
    listAdjustments,
    listBatches,
    listCostAdjustments,
    listReservations,
    listSerials,
    listStateChanges,
    listStockBalances,
    listStockCounts,
    listTransfers,
    listValuationLayers,
} from '../inventoryApi';
import { inventoryPermissions } from '../inventoryPermissions';
import { AvailabilityTab, DashboardTab } from '../components/InventoryOverview';
import {
    AllocationsTab,
    AdjustmentsTab,
    CountsTab,
    ReservationsTab,
    TransfersTab,
} from '../components/InventoryWorkflowTabs';
import {
    AuditTab,
    CostingTab,
    ReportsTab,
    TrackingTab,
} from '../components/InventoryReferenceTabs';

type Tab = 'dashboard' | 'availability' | 'reservations' | 'allocations' | 'adjustments' | 'transfers' | 'counts' | 'costing' | 'tracking' | 'audit' | 'reports';

const tabs = [
    { id: 'dashboard' as Tab, label: 'Dashboard' },
    { id: 'availability' as Tab, label: 'Availability' },
    { id: 'reservations' as Tab, label: 'Reservations' },
    { id: 'allocations' as Tab, label: 'Allocations' },
    { id: 'adjustments' as Tab, label: 'Adjustments' },
    { id: 'transfers' as Tab, label: 'Transfers' },
    { id: 'counts' as Tab, label: 'Stock counts' },
    { id: 'costing' as Tab, label: 'Costing' },
    { id: 'tracking' as Tab, label: 'Batch/serial' },
    { id: 'audit' as Tab, label: 'Audit' },
    { id: 'reports' as Tab, label: 'Reports' },
];

export default function InventoryPage() {
    const auth = useAuth();
    const can = (permission: string) => hasPermission(auth, permission);
    const canAny = (permissions: readonly string[]) => permissions.some(can);
    const [tab, setTab] = useState<Tab>('dashboard');
    const [page, setPage] = useState(1);
    const [itemFilter, setItemFilter] = useState<NamedResource | null>(null);

    const canStockView = can(inventoryPermissions.stockView);
    const canAuditView = can(inventoryPermissions.auditView);
    const canReservationsView = can(inventoryPermissions.reservationsView);
    const canReservationsManage = can(inventoryPermissions.reservationsManage);
    const canAllocationsView = can(inventoryPermissions.allocationsView);
    const canAllocationsManage = can(inventoryPermissions.allocationsManage);
    const canAllocationsIssue = can(inventoryPermissions.allocationsIssue);
    const canAdjustmentsView = can(inventoryPermissions.adjustmentsView);
    const canAdjustmentsManage = can(inventoryPermissions.adjustmentsManage);
    const canAdjustmentsPost = can(inventoryPermissions.adjustmentsPost);
    const canTransfersView = can(inventoryPermissions.transfersView);
    const canTransfersManage = can(inventoryPermissions.transfersManage);
    const canTransfersDispatch = can(inventoryPermissions.transfersDispatch);
    const canTransfersReceive = can(inventoryPermissions.transfersReceive);
    const canValuationView = can(inventoryPermissions.valuationView);
    const canCostAdjustmentsView = can(inventoryPermissions.costAdjustmentsView);
    const canCostAdjustmentsManage = can(inventoryPermissions.costAdjustmentsManage);
    const canCostAdjustmentsPost = can(inventoryPermissions.costAdjustmentsPost);
    const canStockCountsView = can(inventoryPermissions.stockCountsView);
    const canStockCountsManage = can(inventoryPermissions.stockCountsManage);
    const canStockCountsApprove = can(inventoryPermissions.stockCountsApprove);
    const canStockCountsPost = can(inventoryPermissions.stockCountsPost);
    const canTrackingView = can(inventoryPermissions.trackingView);
    const canTrackingManage = can(inventoryPermissions.trackingManage);
    const canOpenInventory = canAny(Object.values(inventoryPermissions));

    const tabAccess: Record<Tab, boolean> = {
        dashboard: canStockView || canAuditView,
        availability: canStockView,
        reservations: canReservationsView || canReservationsManage,
        allocations: canAllocationsView || canAllocationsManage || canAllocationsIssue,
        adjustments: canAdjustmentsView || canAdjustmentsManage || canAdjustmentsPost,
        transfers: canTransfersView || canTransfersManage || canTransfersDispatch || canTransfersReceive,
        counts: canStockCountsView || canStockCountsManage || canStockCountsApprove || canStockCountsPost,
        costing: canValuationView || canCostAdjustmentsView || canCostAdjustmentsManage || canCostAdjustmentsPost,
        tracking: canTrackingView || canTrackingManage,
        audit: canAuditView,
        reports: canOpenInventory,
    };
    const allowedTabs = tabs.filter((candidate) => tabAccess[candidate.id]);
    const activeTab = tabAccess[tab] ? tab : allowedTabs[0]?.id ?? 'dashboard';

    const balances = useApi(
        (signal) => listStockBalances({ page, per_page: 25, item_id: itemFilter?.id }, signal),
        [page, itemFilter?.id],
        canStockView && activeTab === 'dashboard',
    );
    const reservations = useApi(
        (signal) => listReservations({ per_page: 25 }, signal),
        [],
        canReservationsView && ['reservations', 'allocations'].includes(activeTab),
    );
    const allocations = useApi((signal) => listAllocations({ per_page: 25 }, signal), [], canAllocationsView && activeTab === 'allocations');
    const adjustments = useApi((signal) => listAdjustments({ per_page: 25 }, signal), [], canAdjustmentsView && activeTab === 'adjustments');
    const transfers = useApi((signal) => listTransfers({ per_page: 25 }, signal), [], canTransfersView && activeTab === 'transfers');
    const counts = useApi((signal) => listStockCounts({ per_page: 25 }, signal), [], canStockCountsView && activeTab === 'counts');
    const valuationLayers = useApi(
        (signal) => listValuationLayers({ per_page: 25, status: 'open' }, signal),
        [],
        canValuationView && activeTab === 'costing',
    );
    const costAdjustments = useApi(
        (signal) => listCostAdjustments({ per_page: 25 }, signal),
        [],
        canCostAdjustmentsView && activeTab === 'costing',
    );
    const batches = useApi((signal) => listBatches({ per_page: 25 }, signal), [], canTrackingView && activeTab === 'tracking');
    const serials = useApi((signal) => listSerials({ per_page: 25 }, signal), [], canTrackingView && activeTab === 'tracking');
    const states = useApi(
        (signal) => listStateChanges({ per_page: 20 }, signal),
        [],
        canAuditView && ['dashboard', 'audit'].includes(activeTab),
    );

    const reloadInventory = () => {
        balances.reload();
        reservations.reload();
        allocations.reload();
        adjustments.reload();
        transfers.reload();
        counts.reload();
        valuationLayers.reload();
        costAdjustments.reload();
        batches.reload();
        serials.reload();
        states.reload();
    };

    return (
        <>
            <ContentHeader title="Inventory" description="Availability, reservations, allocations, transfers, counts, tracking, and reports." />
            {allowedTabs.length === 0 ? (
                <Panel title="Inventory permission required">
                    <p className="text-sm text-slate-600">You do not have permission to access inventory workflows.</p>
                </Panel>
            ) : (
                <>
                    <Tabs tabs={allowedTabs} active={activeTab} onChange={setTab} />
                    <div className="mt-5">
                        {activeTab === 'dashboard' && (
                            <DashboardTab
                                balances={balances}
                                states={states}
                                itemFilter={itemFilter}
                                setItemFilter={setItemFilter}
                                page={page}
                                setPage={setPage}
                            />
                        )}
                        {activeTab === 'availability' && <AvailabilityTab />}
                        {activeTab === 'reservations' && (
                            <ReservationsTab
                                data={reservations.data?.data ?? []}
                                loading={reservations.loading}
                                error={reservations.error}
                                reload={reloadInventory}
                                canManage={canReservationsManage}
                            />
                        )}
                        {activeTab === 'allocations' && (
                            <AllocationsTab
                                data={allocations.data?.data ?? []}
                                reservations={reservations.data?.data ?? []}
                                loading={allocations.loading}
                                error={allocations.error}
                                reload={reloadInventory}
                                canManage={canAllocationsManage}
                                canIssue={canAllocationsIssue}
                            />
                        )}
                        {activeTab === 'adjustments' && (
                            <AdjustmentsTab
                                data={adjustments.data?.data ?? []}
                                loading={adjustments.loading}
                                error={adjustments.error}
                                reload={reloadInventory}
                                canManage={canAdjustmentsManage}
                                canPost={canAdjustmentsPost}
                            />
                        )}
                        {activeTab === 'transfers' && (
                            <TransfersTab
                                data={transfers.data?.data ?? []}
                                loading={transfers.loading}
                                error={transfers.error}
                                reload={reloadInventory}
                                canManage={canTransfersManage}
                                canDispatch={canTransfersDispatch}
                                canReceive={canTransfersReceive}
                            />
                        )}
                        {activeTab === 'counts' && (
                            <CountsTab
                                data={counts.data?.data ?? []}
                                loading={counts.loading}
                                error={counts.error}
                                reload={reloadInventory}
                                canManage={canStockCountsManage}
                                canApprove={canStockCountsApprove}
                                canPost={canStockCountsPost}
                            />
                        )}
                        {activeTab === 'costing' && (
                            <CostingTab
                                data={costAdjustments.data?.data ?? []}
                                layers={valuationLayers.data?.data ?? []}
                                layersLoading={valuationLayers.loading}
                                layersError={valuationLayers.error}
                                loading={costAdjustments.loading}
                                error={costAdjustments.error}
                                reload={reloadInventory}
                                canManage={canCostAdjustmentsManage}
                                canPost={canCostAdjustmentsPost}
                            />
                        )}
                        {activeTab === 'tracking' && <TrackingTab batches={batches} serials={serials} canManage={canTrackingManage} reload={reloadInventory} />}
                        {activeTab === 'audit' && <AuditTab states={states} />}
                        {activeTab === 'reports' && <ReportsTab />}
                    </div>
                </>
            )}
        </>
    );
}
