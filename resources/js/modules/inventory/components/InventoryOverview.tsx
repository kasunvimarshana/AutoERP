import { useMemo, useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { humanize } from '@/shared/utils/object';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouses } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';
import {
    getAvailability,
    listStateChanges,
    listStockBalances,
} from '../inventoryApi';
import type { InventoryAvailability, InventoryRecord } from '../inventoryTypes';
import { emptyInventoryDimensions, InventoryDimensionFields } from './InventoryDimensionFields';
import {
    type ApiResult,
    quantity,
    RecordList,
    relation,
    sumDecimals,
    zeroDecimal,
} from './inventoryUi';

export function DashboardTab({
    balances,
    states,
    itemFilter,
    setItemFilter,
    page,
    setPage,
}: {
    balances: ApiResult<Awaited<ReturnType<typeof listStockBalances>>>;
    states: ApiResult<Awaited<ReturnType<typeof listStateChanges>>>;
    itemFilter: NamedResource | null;
    setItemFilter: (value: NamedResource | null) => void;
    page: number;
    setPage: (page: number) => void;
}) {
    const totals = useMemo(() => {
        const rows = balances.data?.data ?? [];

        return {
            balances: rows.length,
            onHand: sumDecimals(rows.map((row) => String(row.quantity_on_hand ?? zeroDecimal))),
            available: sumDecimals(rows.map((row) => String(row.quantity_available ?? zeroDecimal))),
            reserved: sumDecimals(rows.map((row) => String(row.quantity_reserved ?? zeroDecimal))),
            allocated: sumDecimals(rows.map((row) => String(row.quantity_allocated ?? zeroDecimal))),
        };
    }, [balances.data?.data]);

    return (
        <div className="space-y-5">
            <Panel>
                <DetailGrid items={[
                    { label: 'Rows on page', value: totals.balances },
                    { label: 'Page on hand', value: <QuantityDisplay value={totals.onHand} /> },
                    { label: 'Page available', value: <QuantityDisplay value={totals.available} /> },
                    { label: 'Page reserved', value: <QuantityDisplay value={totals.reserved} /> },
                    { label: 'Page allocated', value: <QuantityDisplay value={totals.allocated} /> },
                ]} />
            </Panel>
            <div className="grid gap-5 xl:grid-cols-[1fr_24rem]">
                <Panel title="Stock balances">
                    <div className="mb-4 max-w-md">
                        <LookupSelect
                            label="Item"
                            value={itemFilter}
                            onChange={(value) => {
                                setItemFilter(value);
                                setPage(1);
                            }}
                            search={lookupApi.stockableItems}
                            placeholder="Search stockable items..."
                        />
                    </div>
                    <ErrorAlert error={balances.error} />
                    {balances.loading ? <LoadingState /> : <BalanceTable rows={balances.data?.data ?? []} />}
                    <Pagination meta={balances.data?.meta} onPageChange={setPage} />
                    <div className="sr-only">Current balance page {page}</div>
                </Panel>
                <Panel title="Recent state changes">
                    <ErrorAlert error={states.error} />
                    {states.loading ? <LoadingState /> : (
                        <DataTable
                            rows={states.data?.data ?? []}
                            rowKey={(row) => row.id}
                            columns={[
                                { key: 'item', header: 'Item', render: (row) => relation(row.item) },
                                { key: 'state', header: 'State', render: (row) => `${humanize(String(row.from_state ?? ''))} -> ${humanize(String(row.to_state ?? ''))}` },
                                { key: 'qty', header: 'Qty', render: (row) => quantity(row.quantity) },
                            ]}
                        />
                    )}
                </Panel>
            </div>
        </div>
    );
}

export function AvailabilityTab() {
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [dimensions, setDimensions] = useState(emptyInventoryDimensions);
    const [availability, setAvailability] = useState<InventoryAvailability | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [checking, setChecking] = useState(false);

    return (
        <Panel title="Availability lookup">
            <form
                className="grid gap-4 lg:grid-cols-[1fr_1fr_auto]"
                onSubmit={async (event) => {
                    event.preventDefault();
                    if (!item || !warehouse || checking) {
                        return;
                    }

                    setChecking(true);
                    setError(null);
                    try {
                        setAvailability(await getAvailability({
                            item_id: item.id,
                            warehouse_id: warehouse.id,
                            item_variant_id: dimensions.itemVariant?.id,
                            warehouse_location_id: dimensions.warehouseLocation?.id,
                            batch_id: dimensions.batch?.id,
                        }));
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setChecking(false);
                    }
                }}
            >
                <LookupSelect label="Item" value={item} onChange={(value) => { setItem(value); setDimensions(emptyInventoryDimensions()); setAvailability(null); }} search={lookupApi.stockableItems} placeholder="Search stockable items..." />
                <LookupSelect label="Warehouse" value={warehouse} onChange={(value) => { setWarehouse(value); setDimensions({ ...dimensions, warehouseLocation: null, serial: null }); setAvailability(null); }} search={searchWarehouses} placeholder="Search warehouses..." loadOnOpen minSearchLength={0} />
                <div className="flex items-end">
                    <Button type="submit" loading={checking} disabled={!item || !warehouse}>Check</Button>
                </div>
                <InventoryDimensionFields
                    item={item}
                    warehouse={warehouse}
                    value={dimensions}
                    onChange={(value) => { setDimensions(value); setAvailability(null); }}
                    includeSerial={false}
                    includeUom={false}
                />
            </form>
            <div className="mt-4"><ErrorAlert error={error} /></div>
            {availability && (
                <div className="mt-5">
                    <DetailGrid items={[
                        { label: 'On hand', value: quantity(availability.quantityOnHand) },
                        { label: 'Available', value: quantity(availability.quantityAvailable) },
                        { label: 'Reserved', value: quantity(availability.quantityReserved) },
                        { label: 'Allocated', value: quantity(availability.quantityAllocated) },
                        { label: 'In transit', value: quantity(availability.quantityInTransit) },
                        { label: 'Damaged', value: quantity(availability.quantityDamaged) },
                        { label: 'Quarantine', value: quantity(availability.quantityQuarantine) },
                        { label: 'Expired', value: quantity(availability.quantityExpired) },
                        { label: 'Scrapped', value: quantity(availability.quantityScrapped) },
                        { label: 'Total', value: quantity(availability.quantityTotal) },
                    ]} />
                </div>
            )}
        </Panel>
    );
}

function BalanceTable({ rows }: { rows: InventoryRecord[] }) {
    return <RecordList rows={rows} columns={[
        { key: 'item', header: 'Item', render: (row) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row) => relation(row.warehouse) },
        { key: 'location', header: 'Location', render: (row) => relation(row.warehouse_location) },
        { key: 'on_hand', header: 'On hand', render: (row) => quantity(row.quantity_on_hand) },
        { key: 'reserved', header: 'Reserved', render: (row) => quantity(row.quantity_reserved) },
        { key: 'allocated', header: 'Allocated', render: (row) => quantity(row.quantity_allocated) },
        { key: 'available', header: 'Available', render: (row) => quantity(row.quantity_available) },
        { key: 'states', header: 'Held states', render: (row) => `${row.quantity_damaged ?? zeroDecimal} damaged, ${row.quantity_quarantine ?? zeroDecimal} quarantine` },
    ]} />;
}
