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
import { useInventoryStockMovements } from '../hooks';
import type { InventoryStockMovementRecord } from '../types';

export function InventoryStockMovementsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const warehouseId = parsePositiveInteger(searchParams.get('warehouseId'), 0);
    const productId = searchParams.get('productId') ?? '';
    const movementType = searchParams.get('movementType') ?? '';
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const selectedWarehouseId = warehouseId || warehousesQuery.data?.items[0]?.id || 0;
    const movementsQuery = useInventoryStockMovements(
        selectedWarehouseId,
        {
            tenant_id: tenantId,
            page: 1,
            per_page: 100,
            product_id: productId ? Number(productId) : undefined,
            movement_type: movementType || undefined,
            sort: 'performed_at:desc',
        },
        selectedWarehouseId > 0,
    );

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

    const columns: DataTableColumn<InventoryStockMovementRecord>[] = [
        { key: 'performed_at', header: 'Date', render: (row) => <span className="text-sm text-stone-700">{formatDateTime(row.performed_at)}</span> },
        { key: 'product_id', header: 'Product', render: (row) => <span className="font-medium text-stone-950">#{row.product_id}</span> },
        { key: 'movement_type', header: 'Movement', render: (row) => <span className="text-sm capitalize text-stone-700">{row.movement_type.replaceAll('_', ' ')}</span> },
        { key: 'from_location_id', header: 'From', render: (row) => <span className="text-sm text-stone-700">{row.from_location_id ? `#${row.from_location_id}` : '-'}</span> },
        { key: 'to_location_id', header: 'To', render: (row) => <span className="text-sm text-stone-700">{row.to_location_id ? `#${row.to_location_id}` : '-'}</span> },
        { key: 'quantity', header: 'Quantity', render: (row) => <span className="text-sm text-stone-700">{formatQuantity(row.quantity)}</span> },
        { key: 'unit_cost', header: 'Unit Cost', render: (row) => <span className="text-sm text-stone-700">{formatCurrency(row.unit_cost)}</span> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Inventory' }, { label: 'Stock Movements' }]} description="Track warehouse movements using the supported backend filters for product and movement type." title="Stock Movements" />

            <ContentCard className="p-0">
                <TableToolbar description="Switch warehouses and narrow movement history using only the filters supported by the backend API contract." title="Inventory stock movements">
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
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => updateParams({ productId: event.target.value || undefined })} placeholder="Product ID" value={productId} />
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => updateParams({ movementType: event.target.value || undefined })} placeholder="Movement type" value={movementType} />
                            </div>
                        }
                    />
                </TableToolbar>

                {warehousesQuery.isPending || movementsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : warehousesQuery.isError || movementsQuery.isError ? (
                    <ErrorState className="m-6" description={(warehousesQuery.error ?? movementsQuery.error)?.message ?? 'Unable to load stock movements.'} title="Unable to load stock movements" />
                ) : (
                    <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No stock movements match the selected warehouse and filters." title="No stock movements found" />} getRowKey={(row) => row.id} rows={movementsQuery.data.items} />
                )}
            </ContentCard>
        </div>
    );
}
