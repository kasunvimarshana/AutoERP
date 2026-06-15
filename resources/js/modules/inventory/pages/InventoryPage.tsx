import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
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
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedTab = searchParams.get('view');
    const initialTab = tabs.some((item) => item.id === requestedTab) ? requestedTab as Tab : 'dashboard';
    const [tab, setTab] = useState<Tab>(initialTab);
    const [page, setPage] = useState(1);
    const [itemFilter, setItemFilter] = useState<NamedResource | null>(null);
    const balances = useApi(
        (signal) => listStockBalances({ page, per_page: 25, item_id: itemFilter?.id }, signal),
        [page, itemFilter?.id],
    );
    const reservations = useApi(
        (signal) => listReservations({ per_page: 25 }, signal),
        [],
        ['reservations', 'allocations'].includes(tab),
    );
    const allocations = useApi((signal) => listAllocations({ per_page: 25 }, signal), [], tab === 'allocations');
    const adjustments = useApi((signal) => listAdjustments({ per_page: 25 }, signal), [], tab === 'adjustments');
    const transfers = useApi((signal) => listTransfers({ per_page: 25 }, signal), [], tab === 'transfers');
    const counts = useApi((signal) => listStockCounts({ per_page: 25 }, signal), [], tab === 'counts');
    const valuationLayers = useApi(
        (signal) => listValuationLayers({ per_page: 25, status: 'open' }, signal),
        [],
        tab === 'costing',
    );
    const costAdjustments = useApi(
        (signal) => listCostAdjustments({ per_page: 25 }, signal),
        [],
        tab === 'costing',
    );
    const batches = useApi((signal) => listBatches({ per_page: 25 }, signal), [], tab === 'tracking');
    const serials = useApi((signal) => listSerials({ per_page: 25 }, signal), [], tab === 'tracking');
    const states = useApi(
        (signal) => listStateChanges({ per_page: 20 }, signal),
        [],
        ['dashboard', 'audit'].includes(tab),
    );

    useEffect(() => {
        const next = tabs.some((item) => item.id === requestedTab) ? requestedTab as Tab : 'dashboard';
        setTab(next);
        setPage(1);
        setItemFilter(null);
    }, [requestedTab]);

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
            <Tabs tabs={tabs} active={tab} onChange={(next) => {
                setTab(next);
                setPage(1);
                setItemFilter(null);
                setSearchParams({ view: next });
            }} />
            <div className="mt-5">
                {tab === 'dashboard' && (
                    <DashboardTab
                        balances={balances}
                        states={states}
                        itemFilter={itemFilter}
                        setItemFilter={setItemFilter}
                        page={page}
                        setPage={setPage}
                    />
                )}
                {tab === 'availability' && <AvailabilityTab />}
                {tab === 'reservations' && (
                    <ReservationsTab
                        data={reservations.data?.data ?? []}
                        loading={reservations.loading}
                        error={reservations.error}
                        reload={reloadInventory}
                    />
                )}
                {tab === 'allocations' && (
                    <AllocationsTab
                        data={allocations.data?.data ?? []}
                        reservations={reservations.data?.data ?? []}
                        loading={allocations.loading}
                        error={allocations.error}
                        reload={reloadInventory}
                    />
                )}
                {tab === 'adjustments' && (
                    <AdjustmentsTab
                        data={adjustments.data?.data ?? []}
                        loading={adjustments.loading}
                        error={adjustments.error}
                        reload={reloadInventory}
                    />
                )}
                {tab === 'transfers' && (
                    <TransfersTab
                        data={transfers.data?.data ?? []}
                        loading={transfers.loading}
                        error={transfers.error}
                        reload={reloadInventory}
                    />
                )}
                {tab === 'counts' && (
                    <CountsTab
                        data={counts.data?.data ?? []}
                        loading={counts.loading}
                        error={counts.error}
                        reload={reloadInventory}
                    />
                )}
                {tab === 'costing' && (
                    <CostingTab
                        data={costAdjustments.data?.data ?? []}
                        layers={valuationLayers.data?.data ?? []}
                        layersLoading={valuationLayers.loading}
                        layersError={valuationLayers.error}
                        loading={costAdjustments.loading}
                        error={costAdjustments.error}
                        reload={reloadInventory}
                    />
                )}
                {tab === 'tracking' && <TrackingTab batches={batches} serials={serials} />}
                {tab === 'audit' && <AuditTab states={states} />}
                {tab === 'reports' && <ReportsTab />}
            </div>
        </>
    );
}
