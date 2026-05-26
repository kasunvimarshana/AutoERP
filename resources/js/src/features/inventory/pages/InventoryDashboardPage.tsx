import { Link } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { useTenant } from '../../auth/context/TenantContext';
import { useTransferOrders, useCycleCounts, useStockReservations, useValuationConfigs, useInventoryStockLevels } from '../hooks';
import { useWarehouses } from '../../warehouse/hooks';

export function InventoryDashboardPage() {
    const { tenantId } = useTenant();
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, per_page: 100, page: 1, sort: 'name:asc' });
    const selectedWarehouseId = warehousesQuery.data?.items[0]?.id ?? 0;
    const stockLevelsQuery = useInventoryStockLevels(selectedWarehouseId, tenantId, 1, 10, selectedWarehouseId > 0);
    const transferOrdersQuery = useTransferOrders({ tenant_id: tenantId, page: 1, per_page: 10 });
    const cycleCountsQuery = useCycleCounts({ tenant_id: tenantId, page: 1, per_page: 10 });
    const reservationsQuery = useStockReservations({ tenant_id: tenantId, page: 1, per_page: 10 });
    const valuationConfigsQuery = useValuationConfigs({ tenant_id: tenantId, page: 1, per_page: 10 });

    const error =
        warehousesQuery.error ??
        stockLevelsQuery.error ??
        transferOrdersQuery.error ??
        cycleCountsQuery.error ??
        reservationsQuery.error ??
        valuationConfigsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Inventory' }, { label: 'Inventory Dashboard' }]}
                description="The dashboard summarizes the active inventory workflows already exposed by the backend: stock visibility, transfers, cycle counts, reservations, and valuation setup."
                title="Inventory Dashboard"
            />

            {warehousesQuery.isPending || transferOrdersQuery.isPending || cycleCountsQuery.isPending || reservationsQuery.isPending || valuationConfigsQuery.isPending ? (
                <LoadingState lines={8} />
            ) : error ? (
                <ErrorState description={error.message} title="Unable to load inventory dashboard" />
            ) : (
                <>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <ContentCard>
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Warehouses in scope</p>
                            <p className="mt-2 text-3xl font-semibold text-stone-950">{warehousesQuery.data?.items.length ?? 0}</p>
                            <p className="mt-2 text-sm text-stone-600">Selected stock overview uses {warehousesQuery.data?.items[0]?.name ?? 'the first available warehouse'}.</p>
                        </ContentCard>
                        <ContentCard>
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Transfer Orders</p>
                            <p className="mt-2 text-3xl font-semibold text-stone-950">{transferOrdersQuery.data?.meta?.total ?? transferOrdersQuery.data?.items.length ?? 0}</p>
                            <p className="mt-2 text-sm text-stone-600">Inter-warehouse movements waiting for approval, transit, or receipt.</p>
                        </ContentCard>
                        <ContentCard>
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Cycle Counts</p>
                            <p className="mt-2 text-3xl font-semibold text-stone-950">{cycleCountsQuery.data?.meta?.total ?? cycleCountsQuery.data?.items.length ?? 0}</p>
                            <p className="mt-2 text-sm text-stone-600">Count programs ready to start, continue, or complete.</p>
                        </ContentCard>
                        <ContentCard>
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Reservations</p>
                            <p className="mt-2 text-3xl font-semibold text-stone-950">{reservationsQuery.data?.meta?.total ?? reservationsQuery.data?.items.length ?? 0}</p>
                            <p className="mt-2 text-sm text-stone-600">Reserved inventory records including items eligible for expiry release.</p>
                        </ContentCard>
                    </div>

                    <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                        <ContentCard>
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold text-stone-950">Operational shortcuts</h2>
                                    <p className="mt-1 text-sm text-stone-600">Jump directly into stock, transfer, count, or reservation workflows.</p>
                                </div>
                            </div>
                            <div className="mt-5 grid gap-3 md:grid-cols-2">
                                <Link to="/inventory/stock-levels"><Button className="w-full" variant="secondary">Open Stock Levels</Button></Link>
                                <Link to="/inventory/movements"><Button className="w-full" variant="secondary">Open Stock Movements</Button></Link>
                                <Link to="/inventory/transfer-orders"><Button className="w-full" variant="secondary">Manage Transfer Orders</Button></Link>
                                <Link to="/inventory/cycle-counts"><Button className="w-full" variant="secondary">Manage Cycle Counts</Button></Link>
                                <Link to="/inventory/stock-reservations"><Button className="w-full" variant="secondary">Manage Reservations</Button></Link>
                                <Link to="/inventory/valuation-configs"><Button className="w-full" variant="secondary">Open Valuation Configs</Button></Link>
                            </div>
                        </ContentCard>

                        <ContentCard>
                            <h2 className="text-lg font-semibold text-stone-950">Selected warehouse stock snapshot</h2>
                            <p className="mt-1 text-sm text-stone-600">This shows the first warehouse in the current tenant as a quick operational reference.</p>
                            {stockLevelsQuery.isPending ? (
                                <LoadingState className="mt-4" lines={5} />
                            ) : stockLevelsQuery.isError ? (
                                <ErrorState className="mt-4" description={stockLevelsQuery.error.message} title="Unable to load stock snapshot" />
                            ) : (
                                <div className="mt-5 space-y-3">
                                    {(stockLevelsQuery.data?.items ?? []).slice(0, 5).map((level) => (
                                        <div key={level.id} className="rounded-2xl border border-stone-200/80 bg-stone-50/80 px-4 py-3">
                                            <p className="text-sm font-medium text-stone-950">Product #{level.product_id}</p>
                                            <p className="mt-1 text-sm text-stone-600">Location #{level.location_id}</p>
                                        </div>
                                    ))}
                                    {stockLevelsQuery.data?.items.length === 0 ? <p className="text-sm text-stone-500">No stock levels available yet.</p> : null}
                                </div>
                            )}
                        </ContentCard>
                    </div>
                </>
            )}
        </div>
    );
}
