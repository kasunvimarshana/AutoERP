import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { useTenant } from '../../auth/context/TenantContext';
import { formatCurrency, formatDateTime, formatQuantity, parsePositiveInteger } from '../../shared/utils';
import { useWarehouses } from '../../warehouse/hooks';
import { useInventoryStockLevels } from '../hooks';
import type { InventoryStockLevelRecord } from '../types';

export function InventoryStockLevelsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const warehouseId = parsePositiveInteger(searchParams.get('warehouseId'), 0);
    const productFilter = searchParams.get('product') ?? '';
    const locationFilter = searchParams.get('location') ?? '';
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const selectedWarehouseId = warehouseId || warehousesQuery.data?.items[0]?.id || 0;
    const stockLevelsQuery = useInventoryStockLevels(selectedWarehouseId, tenantId, 1, 100, selectedWarehouseId > 0);

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            return next;
        });
    }

    const filteredRows = useMemo(
        () =>
            (stockLevelsQuery.data?.items ?? []).filter((row) => {
                const matchesProduct = !productFilter || String(row.product_id).includes(productFilter);
                const matchesLocation = !locationFilter || String(row.location_id).includes(locationFilter);
                return matchesProduct && matchesLocation;
            }),
        [locationFilter, productFilter, stockLevelsQuery.data?.items],
    );

    const columns: DataTableColumn<InventoryStockLevelRecord>[] = [
        { key: 'product_id', header: 'Product', render: (row) => <span className="font-medium text-stone-950">#{row.product_id}</span> },
        { key: 'location_id', header: 'Location', render: (row) => <span className="text-sm text-stone-700">#{row.location_id}</span> },
        { key: 'on_hand', header: 'On Hand', render: (row) => <span className="text-sm text-stone-700">{formatQuantity(row.quantity_on_hand)}</span> },
        { key: 'reserved', header: 'Reserved', render: (row) => <span className="text-sm text-stone-700">{formatQuantity(row.quantity_reserved)}</span> },
        { key: 'unit_cost', header: 'Unit Cost', render: (row) => <span className="text-sm text-stone-700">{formatCurrency(row.unit_cost)}</span> },
        { key: 'last_movement_at', header: 'Last Movement', render: (row) => <span className="text-sm text-stone-700">{formatDateTime(row.last_movement_at)}</span> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Inventory' }, { label: 'Stock Levels' }]} description="Warehouse-level stock visibility now lives in a dedicated operational table with tenant-safe warehouse selection and local filters." title="Stock Levels" />

            <ContentCard className="p-0">
                <TableToolbar description="Switch warehouses with the supported backend selector, then filter the current result set locally by product or location." title="Inventory stock levels">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Select className="w-full md:max-w-[14rem]" onChange={(event) => updateParams({ warehouseId: event.target.value || undefined })} value={selectedWarehouseId ? String(selectedWarehouseId) : ''}>
                                    {warehousesQuery.data?.items.map((warehouse) => (
                                        <option key={warehouse.id} value={warehouse.id}>
                                            {warehouse.name}
                                        </option>
                                    ))}
                                </Select>
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => updateParams({ product: event.target.value || undefined })} placeholder="Filter product ID" value={productFilter} />
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => updateParams({ location: event.target.value || undefined })} placeholder="Filter location ID" value={locationFilter} />
                            </div>
                        }
                    />
                </TableToolbar>

                {warehousesQuery.isPending || stockLevelsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : warehousesQuery.isError || stockLevelsQuery.isError ? (
                    <ErrorState className="m-6" description={(warehousesQuery.error ?? stockLevelsQuery.error)?.message ?? 'Unable to load stock levels.'} title="Unable to load stock levels" />
                ) : (
                    <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No stock levels are available for the selected warehouse." title="No stock levels found" />} getRowKey={(row) => row.id} rows={filteredRows} />
                )}
            </ContentCard>
        </div>
    );
}
