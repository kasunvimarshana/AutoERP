import { useState } from 'react';
import { listStockBalances, getAvailability } from '../inventoryApi';
import { useApi } from '@/shared/hooks/useApi';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Input } from '@/shared/components/Input';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { Pagination } from '@/shared/components/Pagination';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { readableRelation } from '@/shared/utils/object';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouses } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';

export default function InventoryPage() {
    const [page, setPage] = useState(1);
    const [itemFilter, setItemFilter] = useState<NamedResource | null>(null);
    const balances = useApi((signal) => listStockBalances({ page, per_page: 25, item_id: itemFilter?.id }, signal), [page, itemFilter?.id]);
    const [availabilityItem, setAvailabilityItem] = useState<NamedResource | null>(null);
    const [availabilityWarehouse, setAvailabilityWarehouse] = useState<NamedResource | null>(null);
    const [availability, setAvailability] = useState<Record<string, unknown> | null>(null);
    const [availabilityError, setAvailabilityError] = useState<ApiError | null>(null);
    const [checking, setChecking] = useState(false);
    return (
        <>
            <ContentHeader title="Inventory" description="Stock balance and availability queries from the Inventory API." />
            <div className="grid gap-5 xl:grid-cols-[1fr_22rem]">
                <div>
                    <div className="mb-4 max-w-md"><LookupSelect label="Filter by item" value={itemFilter} onChange={(value) => { setItemFilter(value); setPage(1); }} search={lookupApi.items} placeholder="Search items..." /></div>
                    <ErrorAlert error={balances.error} />
                    {balances.loading ? <LoadingState /> : (
                        <DataTable
                            rows={balances.data?.data ?? []}
                            rowKey={(row) => row.id}
                            columns={[
                                { key: 'item', header: 'Item', render: (row) => readableRelation(row.item) },
                                { key: 'warehouse', header: 'Warehouse', render: (row) => readableRelation(row.warehouse) },
                                { key: 'on_hand', header: 'On hand', render: (row) => <QuantityDisplay value={row.quantity_on_hand} /> },
                                { key: 'reserved', header: 'Reserved', render: (row) => <QuantityDisplay value={row.quantity_reserved} /> },
                                { key: 'available', header: 'Available', render: (row) => <QuantityDisplay value={row.quantity_available} /> },
                            ]}
                        />
                    )}
                    <Pagination meta={balances.data?.meta} onPageChange={setPage} />
                </div>
                <div className="space-y-5">
                    <Panel title="Availability lookup">
                        <form className="space-y-3" onSubmit={async (event) => {
                            event.preventDefault();
                            setChecking(true);
                            setAvailabilityError(null);
                            try {
                                setAvailability(await getAvailability({ item_id: availabilityItem?.id ?? 0, warehouse_id: availabilityWarehouse?.id ?? 0 }));
                            } catch (error) {
                                setAvailabilityError(toApiError(error));
                            } finally {
                                setChecking(false);
                            }
                        }}>
                            <LookupSelect label="Item" value={availabilityItem} onChange={setAvailabilityItem} search={lookupApi.stockableItems} placeholder="Search stockable items..." />
                            <LookupSelect label="Warehouse" value={availabilityWarehouse} onChange={setAvailabilityWarehouse} search={searchWarehouses} placeholder="Search warehouses..." />
                            <ErrorAlert error={availabilityError} />
                            <Button type="submit" loading={checking}>Check availability</Button>
                            {availability && <pre className="overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{JSON.stringify(availability, null, 2)}</pre>}
                        </form>
                    </Panel>
                    <CapabilityNotice>The backend supports creating and posting adjustments/transfers, but does not expose adjustment or transfer list/detail endpoints yet.</CapabilityNotice>
                </div>
            </div>
        </>
    );
}
