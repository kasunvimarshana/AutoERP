import { useDeferredValue, useMemo, useState } from 'react';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { Select } from '@/shared/components/Select';
import { humanize } from '@/shared/utils/object';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouses } from '@/shared/api/referenceApi';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import {
    listStateChanges,
    listStockBalances,
} from '../inventoryApi';
import type { InventoryRecord, InventoryStockLevel, StockBalance } from '../inventoryTypes';
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
    const [search, setSearch] = useState('');
    const deferredSearch = useDeferredValue(search.trim());
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [stockLevel, setStockLevel] = useState<InventoryStockLevel | ''>('');
    const [page, setPage] = useState(1);
    const balances = useApi(
        (signal) => listStockBalances({
            page,
            per_page: 25,
            search: deferredSearch || undefined,
            warehouse_id: warehouse?.id,
            stock_level: stockLevel || undefined,
        }, signal),
        [page, deferredSearch, warehouse?.id, stockLevel],
    );
    const summary = balances.data?.summary ?? { in_stock: 0, low_stock: 0, out_of_stock: 0 };
    const total = summary.in_stock + summary.low_stock + summary.out_of_stock;

    return (
        <div className="space-y-5">
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StockSummaryCard label="All stock lines" value={total} tone="slate" active={stockLevel === ''} onClick={() => { setStockLevel(''); setPage(1); }} />
                <StockSummaryCard label="In stock" value={summary.in_stock} tone="emerald" active={stockLevel === 'in_stock'} onClick={() => { setStockLevel('in_stock'); setPage(1); }} />
                <StockSummaryCard label="Low stock" value={summary.low_stock} tone="amber" active={stockLevel === 'low_stock'} onClick={() => { setStockLevel('low_stock'); setPage(1); }} />
                <StockSummaryCard label="Out of stock" value={summary.out_of_stock} tone="rose" active={stockLevel === 'out_of_stock'} onClick={() => { setStockLevel('out_of_stock'); setPage(1); }} />
            </div>

            <Panel title="Available stock">
                <div className="mb-5 grid gap-4 lg:grid-cols-3">
                    <Input
                        label="Search"
                        value={search}
                        onChange={(event) => { setSearch(event.target.value); setPage(1); }}
                        placeholder="Item name, code, or SKU"
                    />
                    <LookupSelect
                        label="Warehouse"
                        value={warehouse}
                        onChange={(value) => { setWarehouse(value); setPage(1); }}
                        search={searchWarehouses}
                        placeholder="All warehouses"
                        loadOnOpen
                        minSearchLength={0}
                    />
                    <Select
                        label="Stock level"
                        value={stockLevel}
                        onChange={(event) => { setStockLevel(event.target.value as InventoryStockLevel | ''); setPage(1); }}
                        placeholder="All stock levels"
                        options={[
                            { value: 'in_stock', label: 'In stock' },
                            { value: 'low_stock', label: 'Low stock' },
                            { value: 'out_of_stock', label: 'Out of stock' },
                        ]}
                    />
                </div>
                <ErrorAlert error={balances.error} />
                {balances.loading ? <LoadingState /> : (
                    <DataTable
                        rows={balances.data?.data ?? []}
                        rowKey={(row) => row.id}
                        emptyMessage="No stock balances match these filters."
                        mobileSummary={(row) => <ItemIdentity row={row} />}
                        rowBadge={(row) => <StockLevelBadge level={row.stock_level} />}
                        mobileDetails={(row) => (
                            <div className="grid grid-cols-2 gap-3">
                                <MobileStockDetail label="Available" value={<StockQuantity row={row} value={row.quantity_available} emphasis={row.stock_level} />} />
                                <MobileStockDetail label="Reorder point" value={row.reorder_level == null ? 'Not set' : <StockQuantity row={row} value={row.reorder_level} />} />
                                <MobileStockDetail label="Warehouse" value={relation(row.warehouse)} />
                                <MobileStockDetail label="Location" value={relation(row.warehouse_location)} />
                            </div>
                        )}
                        columns={[
                            { key: 'item', header: 'Item', render: (row) => <ItemIdentity row={row} /> },
                            { key: 'location', header: 'Location', render: (row) => <LocationIdentity row={row} /> },
                            { key: 'available', header: 'Available stock', render: (row) => <StockQuantity row={row} value={row.quantity_available} emphasis={row.stock_level} />, className: 'text-right' },
                            { key: 'level', header: 'Stock level', render: (row) => <StockLevelBadge level={row.stock_level} /> },
                            { key: 'reorder', header: 'Reorder point', render: (row) => row.reorder_level == null ? <span className="text-slate-400">Not set</span> : <StockQuantity row={row} value={row.reorder_level} />, className: 'text-right' },
                            { key: 'detail', header: 'Stock detail', render: (row) => <StockBreakdown row={row} /> },
                        ]}
                    />
                )}
                <Pagination
                    meta={balances.data?.meta}
                    onPageChange={setPage}
                />
            </Panel>
        </div>
    );
}

function StockSummaryCard({ label, value, tone, active, onClick }: {
    label: string;
    value: number;
    tone: 'slate' | 'emerald' | 'amber' | 'rose';
    active: boolean;
    onClick: () => void;
}) {
    const tones = {
        slate: 'bg-slate-100 text-slate-700 ring-slate-300',
        emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-300',
        amber: 'bg-amber-50 text-amber-800 ring-amber-300',
        rose: 'bg-rose-50 text-rose-700 ring-rose-300',
    };

    return (
        <button type="button" onClick={onClick} className={`rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-slate-300 ${active ? 'ring-2 ' + tones[tone].split(' ').at(-1) : ''}`}>
            <div className="flex items-center justify-between gap-3">
                <span className="text-sm font-medium text-slate-600">{label}</span>
                <span className={`h-2.5 w-2.5 rounded-full ${tones[tone].split(' ').slice(0, 2).join(' ')}`} aria-hidden="true" />
            </div>
            <p className="mt-2 text-2xl font-bold tabular-nums text-slate-950">{value}</p>
        </button>
    );
}

function ItemIdentity({ row }: { row: StockBalance }) {
    const item = row.item;

    return (
        <div>
            <div className="font-semibold text-slate-900">{item?.name ?? 'Unknown item'}</div>
            {item?.code && <div className="mt-0.5 text-xs text-slate-500">{item.code}</div>}
        </div>
    );
}

function LocationIdentity({ row }: { row: StockBalance }) {
    return (
        <div>
            <div className="font-medium text-slate-800">{relation(row.warehouse)}</div>
            <div className="mt-0.5 text-xs text-slate-500">{relation(row.warehouse_location)}</div>
        </div>
    );
}

function StockQuantity({ row, value, emphasis }: { row: StockBalance; value?: string | null; emphasis?: InventoryStockLevel }) {
    const unit = row.base_uom?.symbol ?? row.base_uom?.code ?? row.base_uom?.name;
    const emphasisClass = emphasis === 'out_of_stock'
        ? 'text-rose-700'
        : emphasis === 'low_stock'
            ? 'text-amber-700'
            : 'text-slate-900';

    return (
        <span className={`font-semibold tabular-nums ${emphasisClass}`}>
            <QuantityDisplay value={value} />{unit ? <span className="ml-1 text-xs font-medium text-slate-500">{unit}</span> : null}
        </span>
    );
}

function StockLevelBadge({ level }: { level: InventoryStockLevel }) {
    const styles = {
        in_stock: 'bg-emerald-100 text-emerald-700',
        low_stock: 'bg-amber-100 text-amber-800',
        out_of_stock: 'bg-rose-100 text-rose-700',
    };
    const labels = {
        in_stock: 'In stock',
        low_stock: 'Low stock',
        out_of_stock: 'Out of stock',
    };

    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${styles[level]}`}>{labels[level]}</span>;
}

function StockBreakdown({ row }: { row: StockBalance }) {
    return (
        <div className="text-xs leading-5 text-slate-600">
            <div>On hand: <QuantityDisplay value={row.quantity_on_hand} /></div>
            <div>Reserved: <QuantityDisplay value={row.quantity_reserved} /></div>
        </div>
    );
}

function MobileStockDetail({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-1 text-slate-800">{value}</div>
        </div>
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
